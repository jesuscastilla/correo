<?php
/**
 * Newsletter — configuración (plantilla).
 * Copia este archivo como `config.php` en esta misma carpeta y rellena los valores.
 * `config.php` está en .gitignore y NO se sube al repositorio.
 */
return [
    // SMTP de salida (Hostalia / "Dominio Absoluto"). 465 está cerrado → 587 + STARTTLS.
    'smtp_host' => 'smtp.dominioabsoluto.net',
    'smtp_port' => 587,
    'smtp_user' => 'monderas@corrientelebeche.es',
    'smtp_pass' => 'PON-AQUI-LA-CONTRASENA-DEL-BUZON',

    // Remitente del boletín (debe coincidir con el buzón autenticado para SPF/DKIM).
    'from'      => 'monderas@corrientelebeche.es',
    'from_name' => 'Barrioteca Acalencá',

    // Nombre usado en EHLO (el hostname público del webmail).
    'helo' => 'correo.corrientelebeche.es',

    // Lista de destinatarias (CSV: primera columna = email) y fichero de bajas.
    'lista_csv'  => __DIR__ . '/lista.csv',
    'bajas_file' => __DIR__ . '/bajas.txt',

    // URL pública del endpoint de baja (debe ser HTTPS para el "one-click" de Gmail).
    'unsubscribe_base' => 'https://www.corrientelebeche.es/newsletter/baja.php',

    // Pausa entre envíos, en milisegundos. Sé prudente: una IP compartida
    // (acens) + volumen alto = más riesgo de spam. Para listas grandes, plantéate
    // un ESP (ver README.md).
    'pausa_ms' => 1500,
];
