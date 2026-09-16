# Correo — webmail de Lebeche (Roundcube en Docker)

> Estado (2026-09-16): **Roundcube desplegado en el NAS vía Docker** (Container Manager).
> Es solo la **interfaz web**: los buzones y el MX siguen en **Hostalia**. Se publica en
> `https://webmail.corrientelebeche.es/`.

## Arquitectura

- **Contenedor:** `roundcube/roundcubemail:latest` (Apache + PHP 8.4, arm64).
- **Base de datos:** MariaDB 10 del NAS (puerto 3307), base `roundcube`.
- **IMAP/SMTP:** `ssl://imap.corrientelebeche.es:993` / `tls://smtp.corrientelebeche.es:587`
  (⚠️ el puerto 465 está cerrado; usar 587 + STARTTLS).
- **Publicación:** subdominio `webmail.corrientelebeche.es` → Reverse Proxy de DSM → `localhost:8090`.
- **Volúmenes:** `/volume2/docker/roundcube/{html,config,temp}`.

## Ficheros del repo

- `docker/compose.yaml` — stack de Roundcube (secretos en `.env`, no versionado).
- `docker/.env.example` — plantilla de variables secretas.
- `DEPLOY_ROUNDCUBE.md` — guía completa de despliegue y operación.

## DNS (Cloudflare)

- `CNAME webmail → pelotxo.synology.me` 🟠 **Proxied**.

## Notas del dominio

- El correo usa `corrientelebeche.es` (sin `www`): `MX 10 mx.corrientelebeche.es`,
  SPF por redirect a `spf.dominioabsoluto.net`.
- Pendiente (entregabilidad): **DMARC** y **DKIM**.

> 🔐 No subir credenciales ni configuraciones sensibles a este repo (`.env` está en `.gitignore`).

