<?php
/**
 * Newsletter — endpoint de baja.
 *
 * Atiende las dos vías del List-Unsubscribe:
 *   - GET  (enlace normal / mailto)  → muestra una página de confirmación.
 *   - POST (one-click de Gmail/Outlook, RFC 8058) → registra la baja y responde 200.
 *
 * La URL pública debe ser HTTPS: https://www.corrientelebeche.es/newsletter/baja.php?email=...
 */

header('Content-Type: text/html; charset=UTF-8');

// Fichero de bajas. No se lee config.php (no hace falta ni credenciales);
// así el endpoint funciona aunque config.php tenga permisos restringidos.
$bajasFile = __DIR__ . '/bajas.txt';

$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = (string) file_get_contents('php://input');
    parse_str($body, $post);
    $email = $_GET['email'] ?? ($post['email'] ?? '');
} else {
    $email = $_GET['email'] ?? '';
}
$email = strtolower(trim((string) $email));

$registrada = false;
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $existing = file_exists($bajasFile)
        ? file($bajasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        : [];
    if (!in_array($email, $existing, true)) {
        file_put_contents($bajasFile, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    $registrada = true;
}

// One-click (POST): respuesta mínima y 200 OK.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(200);
    exit('unsubscribed');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Baja del boletín — Barrioteca Acalencá</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 34rem; margin: 3rem auto; padding: 0 1rem; line-height: 1.5;">
<?php if ($registrada): ?>
    <h1>Hecho ✔</h1>
    <p>Hemos retirado <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong> de la lista del boletín.</p>
    <p>Ya no recibirás más envíos. ¡Gracias por avisar!</p>
<?php else: ?>
    <h1>No se pudo procesar la baja</h1>
    <p>No se recibió una dirección válida. Si has llegado aquí desde un correo, reenvía el asunto o escribe a <strong>monderas@corrientelebeche.es</strong> y te damos de baja manualmente.</p>
<?php endif; ?>
</body>
</html>
