# Roundcube Webmail — Guía de despliegue y operación

> Estado: **desplegado** (2026-09-16) en el NAS Synology DS223 vía **Docker/Container Manager**.
> Roundcube es solo la **interfaz web**; los buzones y el MX siguen en **Hostalia**.

## Resumen de la arquitectura

```text
Cliente → https://webmail.corrientelebeche.es/   (Cloudflare Proxied, cert Universal SSL)
        → NAS:443 (DSM Reverse Proxy)
        → localhost:8090 (contenedor roundcube, Apache + PHP 8.4)
        → MariaDB 10 del NAS (host.docker.internal:3307, base `roundcube`)
        → IMAP/SMTP de Hostalia (imap:993 / smtp:587)
```

## Ubicaciones

| Qué | Dónde |
|---|---|
| Proyecto Docker | `/volume2/docker/roundcube/` |
| `compose.yaml` | `/volume2/docker/roundcube/compose.yaml` |
| Secretos (`.env`) | `/volume2/docker/roundcube/.env` (chmod 600, NO en git) |
| Volúmenes | `./html` (docroot), `./config` (config extra), `./temp` (sesiones) |
| BD | MariaDB10 del NAS, base `roundcube`, usuario `roundcube` |

## Variables clave (compose.yaml)

- `ROUNDCUBEMAIL_DB_*` → BD MariaDB (`host.docker.internal:3307`).
- `ROUNDCUBEMAIL_DEFAULT_HOST=ssl://imap.dominioabsoluto.net` + `DEFAULT_PORT=993`.
- `ROUNDCUBEMAIL_SMTP_SERVER=tls://smtp.dominioabsoluto.net` + `SMTP_PORT=587`.
  ⚠️ **465 está cerrado** en Hostalia; usar **587 + STARTTLS**.
- `ROUNDCUBEMAIL_USERNAME_DOMAIN=corrientelebeche.es` (permite entrar con el usuario suelto).
- `ROUNDCUBEMAIL_DES_KEY` → fijo (en `.env`); **no cambiarlo** una vez haya datos.

> ⚠️ **Por qué `dominioabsoluto.net` y no `corrientelebeche.es`:** el servidor de correo de Hostalia
> presenta un certificado TLS para `*.dominioabsoluto.net` (DigiCert). Si usas `imap/smtp.corrientelebeche.es`,
> Roundcube da "Error de conexión con el servidor IMAP" porque falla la verificación del certificado.
> `imap/smtp.dominioabsoluto.net` resuelven a las **mismas IPs** y su certificado valida correctamente.

## DNS (Cloudflare) — manual

```
CNAME  webmail  pelotxo.synology.me  🟠 Proxied
CNAME  correo   pelotxo.synology.me  🟠 Proxied
```

## Reverse Proxy (DSM) — manual

`Control Panel → Login Portal → Advanced → Reverse Proxy → Create` (una regla por subdominio):

- **Source:** HTTPS · `webmail.corrientelebeche.es` · 443  ← ⚠️ protocolo **HTTPS** (si queda en HTTP, DSM da "puerto en uso")
- **Source:** HTTPS · `correo.corrientelebeche.es` · 443
- **Destination:** HTTP · `localhost` · 8090
- Certificado: asignar `Cloudflare Origin` a cada regla (si no, Cloudflare en `Full (strict)` da **526**).

## Operación diaria

```bash
# estado
sudo /usr/local/bin/docker compose -f /volume2/docker/roundcube/compose.yaml ps

# logs
sudo /usr/local/bin/docker logs -f roundcube

# reiniciar
cd /volume2/docker/roundcube && sudo /usr/local/bin/docker compose restart

# actualizar imagen
cd /volume2/docker/roundcube && sudo /usr/local/bin/docker compose up -d --pull always

# backup de la BD
/usr/local/mariadb10/bin/mysqldump -u root -p roundcube > /volume2/docker/roundcube/backup-roundcube.sql
```

## Autenticación de correo (SPF / DKIM / DMARC) — ✅ configurado

- **SPF**: `v=spf1 redirect=spf.dominioabsoluto.net` (Hostalia).
- **DKIM**: `domabs._domainkey` → TXT con la clave pública de Hostalia (selector de "Dominio Absoluto"). ⚠️ Clave actual de **1024 bits** (pendiente rotar a 2048).
- **DMARC**: `_dmarc` → `v=DMARC1; p=none; rua=mailto:monderas@corrientelebeche.es; fo=1; adkim=s; aspf=s`.
  - Empezar en `p=none` (monitorizar) y subir a `quarantine`/`reject` cuando todo alinee.

> ✅ **Verificado 2026-09-22** (cabeceras reales de Gmail): `dkim=pass`, `spf=pass`
> y `dmarc=pass` → **la autenticación funciona y no es la causa del SPAM.** La
> salida la hace el pool Postal compartido de acens (`servidor-correo.net`,
> `217.116.26.0/24`). Diagnóstico completo y plan: ver
> [`DIAGNOSTICO_ENTREGABILIDAD.md`](DIAGNOSTICO_ENTREGABILIDAD.md).

## Verificación de entregabilidad

- Enviar un correo desde el webmail a la dirección de **mail-tester.com** → esperar 9-10/10 con DKIM/DMARC `pass`.
- Alta gratuita en **Google Postmaster Tools** (<https://postmaster.google.com/>) y **Microsoft SNDS** para vigilar la reputación real del dominio/pool.
- Ver `DIAGNOSTICO_ENTREGABILIDAD.md` para el diagnóstico del caso SPAM en Gmail (2026-09-22).

## Contactos — ✅ importados de Synology Contacts

- **66 contactos** importados a la libreta del usuario `monderas@corrientelebeche.es`.
- Origen: PostgreSQL `synocontacts` (Synology Contacts) → tabla `addressbook_object.vcard_text` → vCard → Roundcube.

## Seguridad

- `.env` y credenciales **nunca** en git.
- La BD `roundcube` es local al NAS (el router no expone el puerto 3307).
