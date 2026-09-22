<?php
/**
 * Newsletter — envío autohospedado con cabeceras de baja (List-Unsubscribe).
 *
 * Envía un correo individual a cada destinataria (no BCC), por SMTP autenticado
 * de Hostalia, añadiendo:
 *   - List-Unsubscribe        (mailto de baja + URL one-click)
 *   - List-Unsubscribe-Post   (List-Unsubscribe=One-Click, RFC 8058)
 *
 * Uso (desde SSH en el NAS):
 *   php enviar.php "Asunto del boletín" mensaje.html
 *
 * Requiere: config.php (copia de config.example.php) y lista.csv.
 * Las direcciones dadas de baja en bajas.txt se omiten automáticamente.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo se ejecuta por línea de comandos.\n");
}

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "Falta config.php (copia config.example.php y rellena).\n");
    exit(1);
}
$cfg = require $configPath;

$subject  = $argv[1] ?? null;
$bodyFile = $argv[2] ?? null;
if (!$subject || !$bodyFile || !file_exists($bodyFile)) {
    fwrite(STDERR, "Uso: php enviar.php \"Asunto\" mensaje.html\n");
    exit(1);
}

$html = file_get_contents($bodyFile);
// Texto plano derivado del HTML (parte text/plain del multipart/alternative).
$text = trim(strip_tags(str_replace(
    ['<br>', '<br/>', '<br />', '</p>', '</div>', '</h1>', '</h2>', '</h3>', '</li>'],
    "\n",
    $html
)));

// --- Destinatarias (CSV: email[,nombre[,...]]) --------------------------------
$recipients = [];
if (($fh = @fopen($cfg['lista_csv'], 'r')) !== false) {
    while (($row = fgetcsv($fh)) !== false) {
        $email = strtolower(trim($row[0] ?? ''));
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $email;
        }
    }
    fclose($fh);
}
if (!$recipients) {
    fwrite(STDERR, "No hay destinatarias válidas en {$cfg['lista_csv']}\n");
    exit(1);
}

// --- Bajas previas ------------------------------------------------------------
$bajas = [];
if (file_exists($cfg['bajas_file'])) {
    foreach (file($cfg['bajas_file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $b) {
        $bajas[strtolower(trim($b))] = true;
    }
}

// --- Envío --------------------------------------------------------------------
$ok = $skip = $fail = 0;
foreach ($recipients as $email) {
    if (isset($bajas[$email])) {
        echo "SKIP $email (dada de baja)\n";
        $skip++;
        continue;
    }
    try {
        smtp_send($cfg, $email, $subject, $text, $html);
        echo "OK   $email\n";
        $ok++;
        if (!empty($cfg['pausa_ms'])) {
            usleep((int) $cfg['pausa_ms'] * 1000);
        }
    } catch (Exception $e) {
        fwrite(STDERR, "FAIL $email: " . $e->getMessage() . "\n");
        $fail++;
    }
}

echo "\nEnviados: $ok · Omitidos (baja): $skip · Fallos: $fail\n";

// ============================================================================
// SMTP mínimo (STARTTLS + AUTH LOGIN) — sin dependencias externas.
// ============================================================================

function smtp_dialog($fp)
{
    $out = '';
    while (!feof($fp)) {
        $line = fgets($fp, 515);
        if ($line === false) {
            break;
        }
        $out .= $line;
        // Respuestas multilínea: continúan con "250-", terminan con "250 ".
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return $out;
}

function smtp_cmd($fp, $cmd)
{
    fwrite($fp, $cmd . "\r\n");
    return smtp_dialog($fp);
}

function smtp_expect($resp)
{
    $code = (int) substr($resp, 0, 3);
    if ($code >= 400) {
        throw new Exception('SMTP: ' . trim($resp));
    }
    return $resp;
}

function smtp_send(array $cfg, string $to, string $subject, string $text, string $html)
{
    $fp = @fsockopen($cfg['smtp_host'], $cfg['smtp_port'], $errno, $errstr, 30);
    if (!$fp) {
        throw new Exception("no se pudo conectar a {$cfg['smtp_host']}:{$cfg['smtp_port']} — $errstr ($errno)");
    }
    stream_set_timeout($fp, 30);

    smtp_expect(smtp_dialog($fp));
    smtp_expect(smtp_cmd($fp, 'EHLO ' . $cfg['helo']));
    smtp_expect(smtp_cmd($fp, 'STARTTLS'));
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        throw new Exception('fallo al negociar STARTTLS');
    }
    smtp_expect(smtp_cmd($fp, 'EHLO ' . $cfg['helo']));
    smtp_expect(smtp_cmd($fp, 'AUTH LOGIN'));
    smtp_expect(smtp_cmd($fp, base64_encode($cfg['smtp_user'])));
    smtp_expect(smtp_cmd($fp, base64_encode($cfg['smtp_pass'])));
    smtp_expect(smtp_cmd($fp, 'MAIL FROM:<' . $cfg['from'] . '>'));
    smtp_expect(smtp_cmd($fp, 'RCPT TO:<' . $to . '>'));
    smtp_expect(smtp_cmd($fp, 'DATA'));

    fwrite($fp, build_message($cfg, $to, $subject, $text, $html));
    smtp_expect(smtp_dialog($fp)); // respuesta al "."

    smtp_cmd($fp, 'QUIT');
    fclose($fp);
}

function build_message(array $cfg, string $to, string $subject, string $text, string $html)
{
    $boundary = 'b' . md5(uniqid('', true));
    $from = $cfg['from'];
    if (!empty($cfg['from_name'])) {
        $from = '=?UTF-8?B?' . base64_encode($cfg['from_name']) . "?= <{$cfg['from']}>";
    }
    $msgId = time() . '.' . bin2hex(random_bytes(8)) . '@corrientelebeche.es';
    $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $unsubUrl = $cfg['unsubscribe_base'] . '?email=' . urlencode($to);

    $headers = [
        "From: $from",
        "To: $to",
        "Subject: $subjectEnc",
        'Date: ' . date('r'),
        "Message-ID: <$msgId>",
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        "List-Unsubscribe: <$unsubUrl>, <mailto:{$cfg['from']}?subject=unsubscribe>",
        'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
    ];

    $head = implode("\r\n", $headers) . "\r\n\r\n";
    $body = "--$boundary\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
          . $text . "\r\n\r\n"
          . "--$boundary\r\n"
          . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
          . $html . "\r\n"
          . "--$boundary--";

    return $head . $body . "\r\n.\r\n";
}

