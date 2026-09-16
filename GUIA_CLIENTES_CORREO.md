# Guía de uso del correo — Lebeche (`corrientelebeche.es`)

> **Webmail:** `https://webmail.corrientelebeche.es/` (o `https://correo.corrientelebeche.es/`) · **Buzón:** `monderas@corrientelebeche.es`
> Servidor de correo: **Hostalia** (plataforma "Dominio Absoluto").

---

## 1. Datos del servidor (válidos para webmail, móvil y escritorio)

| Parámetro | Valor |
|---|---|
| **Servidor IMAP** (entrada) | `imap.dominioabsoluto.net` |
| **Puerto IMAP** | `993` (SSL/TLS) |
| **Servidor SMTP** (salida) | `smtp.dominioabsoluto.net` |
| **Puerto SMTP** | `587` (STARTTLS) |
| **Usuario** | `monderas@corrientelebeche.es` (email **completo**) |
| **Contraseña** | la del buzón |

> ⚠️ Hay que usar los hostnames del **proveedor** (`dominioabsoluto.net`), **no** `imap/smtp.corrientelebeche.es`:
> el certificado TLS del servidor es para `*.dominioabsoluto.net` y, si no, la conexión falla.

---

## 2. Abrir el correo desde el webmail (Roundcube)

1. Entra en `https://webmail.corrientelebeche.es/` (o `https://correo.corrientelebeche.es/`).
2. **Usuario:** `monderas@corrientelebeche.es` · **Contraseña:** la del buzón.
3. (Opcional) marca **"Recordarme"**.

- **Leer:** clic en un mensaje de la bandeja.
- **Escribir:** botón **"Redactar"** → *Para / Asunto / Cuerpo* → **"Enviar"**.
- **Carpetas:** bandeja de entrada, enviados, papelera… (columna izquierda).
- **Contactos:** pestaña **"Contactos"** → los contactos de Synology Contacts ya están importados (66).

---

## 3. Configurar el correo en el móvil / escritorio

### iOS — Mail (iPhone/iPad)
Ajustes → **Correo → Cuentas → Añadir cuenta → Otros → Añadir cuenta de correo**:
- Nombre, dirección `monderas@corrientelebeche.es` y contraseña.
- **Correo entrante (IMAP):** hostname `imap.dominioabsoluto.net`, usuario (email completo) y contraseña.
- **Correo saliente (SMTP):** hostname `smtp.dominioabsoluto.net`, usuario (email completo) y contraseña.
- Puertos: **IMAP 993 (SSL)** · **SMTP 587 (STARTTLS)**.

### Android — Gmail / app de correo
Añadir cuenta → **Otro** → email completo + contraseña → **configuración manual**:
- **IMAP:** `imap.dominioabsoluto.net` · `993` · SSL/TLS
- **SMTP:** `smtp.dominioabsoluto.net` · `587` · STARTTLS

### Outlook / Thunderbird / otros
Igual que arriba. Tipo de cifrado: **IMAP SSL/TLS (993)** y **SMTP STARTTLS (587)**. Usuario = **email completo**.

---

## 4. Solución de problemas

| Problema | Solución |
|---|---|
| "No se puede conectar al servidor" | Usa `imap/smtp.dominioabsoluto.net` (no `…corrientelebeche.es`) |
| "Contraseña o usuario incorrecto" | El usuario es el **email completo**; revisa la contraseña en Hostalia |
| "No se puede enviar" (SMTP) | Puerto **587 + STARTTLS** (el puerto 465 está cerrado) |
| No veo los contactos | Están en la pestaña **Contactos** del webmail (importados) |
