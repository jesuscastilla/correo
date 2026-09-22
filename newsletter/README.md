# Newsletter autohospedado — Barrioteca Acalencá

Boletín para las socias con **baja en un clic** (List-Unsubscribe), enviado por el
SMTP de Hostalia. Sin servicios de terceros.

## Qué hay en esta carpeta

| Archivo | Función |
|---|---|
| `enviar.php` | Envía el boletín (CLI) a cada destinataria, añadiendo las cabeceras de baja |
| `baja.php` | Endpoint público de baja (GET por enlace/mailto + POST one-click RFC 8058) |
| `config.example.php` | Plantilla de configuración → copiar a `config.php` |
| `lista.example.csv` | Plantilla de destinatarias → copiar a `lista.csv` (primera columna = email) |
| `bajas.txt` | Se crea solo; direcciones dadas de baja (se omiten en el envío) |

## Las dos cabeceras que se generan

```text
List-Unsubscribe: <https://www.corrientelebeche.es/newsletter/baja.php?email=xxx@yyy.zz>, <mailto:monderas@corrientelebeche.es?subject=unsubscribe>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

- `List-Unsubscribe` da el enlace (y el mailto de respaldo) que muestra Gmail/Outlook.
- `List-Unsubscribe-Post` activa el **one-click** (RFC 8058), que Gmail **exige**
  a remitentes masivos: debe ser un **POST HTTPS** → por eso `baja.php` acepta POST.

> ⚠️ Una cabecera sola no basta: si el enlace no lleva a un endpoint real que
> registre la baja, Gmail penaliza. Por eso `baja.php` es imprescindible.

## Despliegue en el NAS

1. Copiar esta carpeta a `/volume1/web/newsletter/` (Web Station). La URL pública
   será `https://www.corrientelebeche.es/newsletter/` (HTTPS ya lo da Cloudflare).
2. `cp config.example.php config.php` y rellenar `smtp_pass` y, si procede,
   `from_name` y `unsubscribe_base`.
3. `cp lista.example.csv lista.csv` y rellenar con los emails de las socias (una por línea; 1ª columna).
4. Probar la baja: abrir `https://www.corrientelebeche.es/newsletter/baja.php?email=prueba@corrientelebeche.es`
   y comprobar que se crea `bajas.txt`.

## Envío (desde SSH en el NAS)

```bash
cd /volume1/web/newsletter
php enviar.php "Boletín de octubre" mensaje.html
```

`enviar.php` omite automáticamente las direcciones de `bajas.txt` y hace una
pequeña pausa entre envíos (`pausa_ms`).

## Avisos importantes (entregabilidad)

- **Volumen:** esto sale por la **IP compartida de acens** (`217.116.26.0/24`),
  la misma que ya sospechamos como causa del SPAM. Para una lista vecinal pequeña
  (decenas / pocos cientos) con pausa entre envíos puede funcionar; **no es un
  motor de mailing masivo**.
- **Contenido:** proporción texto > imágenes, sin acortadores de URL, enlaces
  normales y asunto descriptivo. HTML simple.
- **Ritmo:** no mandar todo de golpe el primer día; la reputación se construye
  poco a poco (ver `../DIAGNOSTICO_ENTREGABILIDAD.md`).
- **Si la lista crece** (o empieza a caer en spam de forma recurrente), un **ESP**
  (Brevo, Mailjet, MailerLite, Amazon SES…) gestiona bajas, rebotes, DKIM y
  reputación por ti. Ahí `List-Unsubscribe` se añade solo. Es la opción correcta
  cuando se supera el umbral de "correo personal".

## Seguridad

- `config.php` (con la contraseña del buzón), `lista.csv` (datos de socias) y
  `bajas.txt` están en `.gitignore` y **no** se suben al repositorio.
- Protege la carpeta si quieres evitar que cualquiera consulte `lista.csv` desde
  la web: en el NAS, o bien fuera del docroot, o un `.htaccess` con `Deny from all`
  para `lista.csv`/`bajas.txt`/`config.php`.
