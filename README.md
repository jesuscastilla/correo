# Correo — servidor de correo de Lebeche (Roundcube Webmail)

> Estado: **pendiente de configurar**. Este repo/carpeta alojará la configuración del servidor
> de correo de la asociación, basado en **Roundcube Webmail** (paquete de **SynoCommunity**) sobre
> el NAS Synology.

## Qué se hará aquí (más adelante)
- Instalar **Roundcube** desde **SynoCommunity** en el NAS.
- Conectar Roundcube al servidor de correo (IMAP/SMTP) del dominio `corrientelebeche.es`.
- Documentar la configuración (bases de datos, vhost de Web Station, HTTPS, usuarios).
- Guardar scripts/plantillas de configuración versionables (sin secretos).

## Notas del dominio
- Dominio: `corrientelebeche.es` (canónico `www` para la web; el correo usa `corrientelebeche.es`).
- Los registros de correo (MX/SPF/imap/pop3/smtp) están en Cloudflare apuntando a Hostalia:
  - `MX 10 mx.corrientelebeche.es`
  - `TXT v=spf1 redirect=spf.dominioabsoluto.net`
  - `A mx/imap/pop3/smtp` → `217.116.0.227/.237/.237/.228`

## Pendiente
- [ ] Instalar Roundcube (SynoCommunity) en el NAS.
- [ ] Configurar la conexión IMAP/SMTP.
- [ ] Publicar el webmail (subruta `corrientelebeche.es/webmail/` o subdominio).
- [ ] HTTPS y acceso.

> 🔐 No subir credenciales ni configuraciones sensibles a este repo.
