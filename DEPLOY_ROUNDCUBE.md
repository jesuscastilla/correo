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
- `ROUNDCUBEMAIL_DEFAULT_HOST=ssl://imap.corrientelebeche.es` + `DEFAULT_PORT=993`.
- `ROUNDCUBEMAIL_SMTP_SERVER=tls://smtp.corrientelebeche.es` + `SMTP_PORT=587`.
  ⚠️ **465 está cerrado** en Hostalia; usar **587 + STARTTLS**.
- `ROUNDCUBEMAIL_DES_KEY` → fijo (en `.env`); **no cambiarlo** una vez haya datos.

## DNS (Cloudflare) — manual

```
CNAME  webmail  pelotxo.synology.me  🟠 Proxied
```

## Reverse Proxy (DSM) — manual

`Control Panel → Login Portal → Advanced → Reverse Proxy → Create`:

- **Source:** HTTPS · `webmail.corrientelebeche.es` · 443  ← ⚠️ protocolo **HTTPS** (si queda en HTTP, DSM da "puerto en uso")
- **Destination:** HTTP · `localhost` · 8090
- Certificado: asignar `Cloudflare Origin` a la regla (si no, Cloudflare en `Full (strict)` da **526**).

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

## Mejoras pendientes (entregabilidad)

- **DMARC** (`_dmarc` TXT) y **DKIM** (pedir el selector a Hostalia).
- Probar envío/recepción con un buzón real y pasar **mail-tester.com**.

## Seguridad

- `.env` y credenciales **nunca** en git.
- La BD `roundcube` es local al NAS (el router no expone el puerto 3307).
