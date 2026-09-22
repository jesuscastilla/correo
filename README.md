# Correo — webmail de Lebeche (Roundcube en Docker)

> Estado (2026-09-16): ✅ **funcionando** — Roundcube desplegado en el NAS vía Docker
> (Container Manager). Es solo la **interfaz web**: los buzones y el MX siguen en **Hostalia**.
> Se publica en `https://webmail.corrientelebeche.es/` y `https://correo.corrientelebeche.es/`.

## Arquitectura

- **Contenedor:** `roundcube/roundcubemail:latest` (Apache + PHP 8.4, arm64).
- **Base de datos:** MariaDB 10 del NAS (puerto 3307), base `roundcube`.
- **IMAP/SMTP:** `ssl://imap.dominioabsoluto.net:993` / `tls://smtp.dominioabsoluto.net:587`
  (⚠️ hay que usar los hostnames del **proveedor**, no `…corrientelebeche.es`: el certificado TLS
  del servidor es para `*.dominioabsoluto.net`. El puerto 465 está cerrado → 587 + STARTTLS).
- **Publicación:** subdominios `webmail` y `correo` → Reverse Proxy de DSM → `localhost:8090`.
- **Volúmenes:** `/volume2/docker/roundcube/{html,config,temp}`.
- **Contactos:** 66 importados desde Synology Contacts (PostgreSQL `synocontacts` → vCard → Roundcube).

## Ficheros del repo

- `docker/compose.yaml` — stack de Roundcube (secretos en `.env`, no versionado).
- `docker/.env.example` — plantilla de variables secretas.
- `DEPLOY_ROUNDCUBE.md` — guía completa de despliegue y operación.
- `GUIA_CLIENTES_CORREO.md` — cómo configurar el correo en móvil/escritorio y usar el webmail.

## DNS (Cloudflare)

- `CNAME webmail → pelotxo.synology.me` 🟠 **Proxied**.
- `CNAME correo → pelotxo.synology.me` 🟠 **Proxied**.

## Reverse Proxy (DSM)

`Panel de control → Portal de inicio de sesión → Avanzado → Proxy inverso` (una regla por subdominio):

- **Origen:** `HTTPS` · `webmail.corrientelebeche.es` · `443`  ← ⚠️ protocolo **HTTPS** (si queda en HTTP, DSM da "puerto en uso")
- **Origen:** `HTTPS` · `correo.corrientelebeche.es` · `443`
- **Destino:** `HTTP` · `localhost` · `8090`
- **Certificado:** asignar **`Cloudflare Origin`** a cada regla (si no, Cloudflare en `Full (strict)` da **Error 526**).

## Autenticación de correo (SPF / DKIM / DMARC) — ✅ configurado

- **SPF:** `v=spf1 redirect=spf.dominioabsoluto.net` (Hostalia).
- **DKIM:** `domabs._domainkey` → TXT con la clave pública de Hostalia (selector de "Dominio Absoluto"). ⚠️ Clave actual de **1024 bits** (pendiente rotar a 2048).
- **DMARC:** `_dmarc` → `v=DMARC1; p=none; rua=mailto:monderas@corrientelebeche.es; fo=1; adkim=s; aspf=s`.
  - Empezar en `p=none` (monitorizar) y subir a `quarantine`/`reject` cuando todo alinee.

> ✅ **Verificado 2026-09-22**: la autenticación (SPF/DKIM/DMARC) está en `pass`
> en Gmail; el SPAM se debe a la reputación del pool saliente de acens. Ver
> [`DIAGNOSTICO_ENTREGABILIDAD.md`](DIAGNOSTICO_ENTREGABILIDAD.md).

> 🔐 No subir credenciales ni configuraciones sensibles a este repo (`.env` está en `.gitignore`).

