# Diagnóstico de entregabilidad — `corrientelebeche.es`

> Fecha: **2026-09-22** · Muestra: `Prueba.eml` enviado desde el webmail a una cuenta
> Gmail (`jesuscastillalacal@gmail.com`), que lo depositó en **SPAM**.

---

## 1. Conclusión (lo importante)

**La autenticación está perfecta.** El correo NO cae en spam por SPF/DKIM/DMARC:
las cabeceras de Gmail muestran `pass` en los tres. La causa es **reputación**
(pool de IP compartido de acens + dominio recién estrenado + mensaje de prueba).

Por eso el arreglo "subir DKIM a 2048 bits" **no resuelve esto por sí solo**
(Gmail ya valida la firma actual). Es una mejora de higiene, no la palanca.

---

## 2. Cabeceras verificadas (del `.eml`, las que decide Gmail)

| Comprobación | Resultado | Detalle |
|---|---|---|
| **SPF** | ✅ `pass` | `domain of monderas@corrientelebeche.es designates 217.116.26.37 as permitted sender` |
| **DKIM** | ✅ `pass` | `header.i=@corrientelebeche.es header.s=domabs` → alineación estricta OK (`d=corrientelebeche.es`) |
| **DMARC** | ✅ `pass` | `p=NONE sp=NONE dis=NONE` → alinea con `adkim=s/aspf=s` |
| **TLS** | ✅ `TLS1.3` | `TLS_AES_256_GCM_SHA384` |
| **rDNS (PTR)** | ✅ | `217.116.26.37` → `relayoutvt05-q02.servidor-correo.net` |

```
Authentication-Results: mx.google.com;
  dkim=pass header.i=@corrientelebeche.es header.s=domabs header.b=fYv41xqX;
  spf=pass (... domain of monderas@corrientelebeche.es designates 217.116.26.37 ...);
  dmarc=pass (p=NONE sp=NONE dis=NONE) header.from=corrientelebeche.es
```

---

## 3. Cadena de envío real (quién transporta el correo)

```text
Roundcube en el NAS (79.117.52.185, sin PTR — hop interno, Gmail no lo evalúa)
  └─ SMTP AUTH (monderas.corrientelebeche.es) ───────────────────────────►
relayout05.dominioabsoluto.net   (217.116.26.51)   ← servidor de entrada Hostalia
  └─ reenvío ────────────────────────────────────────────────────────────►
relayoutvt05-q02.servidor-correo.net (217.116.26.37) ← relay saliente REAL (acens/Postal)
  └─ ESMTPS ─────────────────────────────────────────────────────────────►
mx.google.com
```

**Hallazgo clave:** Hostalia enruta la salida por el pool **Postal compartido de
acens** (`servidor-correo.net`), rango `217.116.26.0/24` (RDAP: `ACENS-MAD-26-L`,
país ES). La IP que ve Gmail es **`217.116.26.37`**, compartida con otros clientes
de acens.

## 4. Reputación de la IP (comprobada 2026-09-22)

| Lista | Estado |
|---|---|
| Barracuda | ✅ no listado |
| SpamCop | ✅ no listado |
| SORBS | ✅ no listado |
| Spamhaus ZEN | ⚠️ no verificable desde resolutor público (devuelve `127.255.255.254` = consulta bloqueada) → comprobar en <https://check.spamhaus.org/> o desde el NAS |

> Gmail **no** usa esas listas negras clásicas: usa su propia heurística de
> reputación (historial del pool + antigüedad/volumen del dominio + engagement).
> Por eso la IP puede estar "limpia" en listas y aun así caer en spam en Gmail.

---

## 5. Causa probable (por orden de peso)

1. **Reputación de IP compartida** del pool acens `217.116.26.0/24` en Gmail
   (neutra/baja por el uso de otros clientes).
2. **Dominio sin historial**: el dominio se estrenó el **2026-09-16**; cero
   volumen/engagement previo → reputación de dominio = 0.
3. **Mensaje de prueba**: asunto `Prueba`, una sola línea, primer contacto con
   esa cuenta Gmail → patrón clásico de spam ("texto mínimo + remitente nuevo").

*(No es autenticación: SPF/DKIM/DMARC están en `pass`.)*

---

## 6. Plan revisado (en este orden)

### A · Calentar reputación (0 €, ~2-3 semanas) — la palanca real
- Enviar **correos reales** (con contenido normal, no tests) a destinatarias reales.
- Pedir a cada destinataria: marcar **"No es spam"** y **añadir a contactos** el
  remitente `monderas@corrientelebeche.es` (sube la reputación de dominio directa).
- Primer contacto en **texto plano**, sin enlaces ni imágenes; asunto descriptivo.
- **No** enviar a grupos grandes de golpe.
- Alta en **Google Postmaster Tools** (gratis): verificar el dominio y vigilar la
  reputación para `gmail.com` → <https://postmaster.google.com/>.
- Alta en **Microsoft SNDS** para `outlook.com/hotmail` → <https://sendersupport.olc.protection.outlook.com/snds/>.

### B · Ticket a Hostalia/acens (ver borrador en §8)
- Reportar que su pool saliente `217.116.26.37` / `servidor-correo.net` está
  siendo marcado como spam por Gmail **a pesar de SPF/DKIM/DMARC `pass`**.
- Preguntar si pueden (1) reasignar a un pool con mejor reputación, (2) ofrecer
  **IP dedicada** (⚠️ solo útil con volumen y *warming*; para un buzón personal
  de bajo volumen suele ser peor), o (3) si hay incidencias conocidas del rango.
- Aprovechar para pedir **rotación de DKIM a 2048 bits** (higiene, ver §7).

### C · Si tras 2-3 semanas sigue en spam → relay ESP (ya justificado)
- Con autenticación perfecta y aún en spam, queda demostrado que el problema es
  el **pool de IP**. Cambiar el pool (relay) ataca la causa exacta.
- Candidatos: **Brevo** (gratis 300/día), **Mailjet**, **SMTP2GO**, **Amazon SES**
  (~0,10 $/1000), **Postmark**. Implica: DKIM propio 2048 del ESP + SPF
  `include:` + ajustar Roundcube (`smtp_user`/`smtp_pass` fijos). MX/IMAP no cambian.
- Trade-off: el correo personal pasa por un tercero (contradice "sin ceder datos
  a terceros" del proyecto) y su AUP está orientada a transaccional/marketing.

---

## 7. DKIM 2048 (higiene, no urgente)

Clave actual del selector `domabs`: **RSA 1024 bits** (SPKI DER = 162 bytes;
una de 2048 daría 294). Gmail la acepta (`dkim=pass`), pero es recomendación
actual del sector usar 2048. Rotación limpia:

1. Hostalia genera la clave nueva y entrega selector+valor TXT.
2. Publicar el selector **nuevo** en Cloudflare (DNS only), sin borrar `domabs`.
3. Activar la firma nueva; esperar unos días; retirar `domabs`.

Verificación DNS (PowerShell):

```powershell
$base='https://cloudflare-dns.com/dns-query'; $h=@{accept='application/dns-json'}
'domabs._domainkey.corrientelebeche.es','_dmarc.corrientelebeche.es' |
  ForEach-Object { Invoke-RestMethod "$base?name=$_&type=TXT" -Headers $h |
    Select-Object -Expand Answer | Select-Object name, data }
```

## 8. Ticket a Hostalia (borrador listo para copiar)

> **Asunto:** Entregabilidad de `corrientelebeche.es` — Gmail marca como SPAM pese a SPF/DKIM/DMARC `pass`
>
> Buenos días. Soy titular de `corrientelebeche.es`, con el correo en vuestra
> plataforma (buzón `monderas@corrientelebeche.es`). Los correos salientes llegan
> a la carpeta de spam de Gmail. Adjunto cabeceras: Gmail confirma
> `dkim=pass`, `spf=pass` y `dmarc=pass`, por lo que **no es un problema de
> autenticación**. La salida se realiza desde la IP `217.116.26.37`
> (`relayoutvt05-q02.servidor-correo.net`, pool Postal de acens).
> Solicito:
> 1. Revisar la **reputación del pool saliente `217.116.26.0/24`** y, si es
>    posible, reasignar a un pool con mejor reputación u ofrecer alternativa
>    (IP dedicada con warming, si la hubiera).
> 2. **Rotar la firma DKIM a RSA 2048 bits** y facilitarme selector y valor TXT
>    para publicar en mi DNS.
> 3. Confirmar el **selector** y el dominio de firma (`d=`) actuales, y si existe
>    panel para gestionar DKIM por mi cuenta.
>
> Gracias.

---

## 9. Criterio Go/No-Go

| Resultado (a 2-3 semanas) | Decisión |
|---|---|
| Los correos entran en bandeja (o Postmaster muestra reputación subiendo) | ✅ Cerrar **sin** ESP |
| Autenticación sigue `pass` pero Gmail/Outlook siguen mandando a spam | ⚠️ Causa = IP compartida de acens ⇒ **ir a relay ESP** (Brevo/SES/Mailjet/SMTP2GO/Postmark) |

## 10. Seguridad (pendiente, sin relación directa)

- `G:\GITHUB\CONTEXT.md` (líneas ~553-568) guarda **contraseñas en texto plano**
  (DSM, SLiMS, buzón, SSH…). No es un repo git (verificado), pero conviene
  moverlo a un gestor de contraseñas o cifrarlo; en especial, **rotar la
  contraseña del buzón** si hubiera la menor sospecha de uso desde IPs ajenas
  (comprobar accesos en Hostalia), porque un buzón comprometido hunde la
  reputación y es un riesgo de seguridad grave.

---

## 11. Qué NO hacer (conceptos erróneos frecuentes)

Verificado en vivo el 2026-09-22. Son consejos genéricos que **no aplican a este
caso** y, en un caso, son contraproducentes:

1. **NO bajar DMARC a alineación relajada (`adkim=r; aspf=r`).**
   El `.eml` demuestra que la firma DKIM usa `d=corrientelebeche.es` (idéntico al
   `From`), así que la alineación **estricta ya da `pass`**. Relajarla no mejora
   nada y **reduce la protección anti-spoofing**. Se mantiene `adkim=s; aspf=s`.

2. **No hay "PTR ausente" en la IP que importa.** La IP saliente que ve Gmail es
   `217.116.26.37`, con PTR `relayoutvt05-q02.servidor-correo.net` que resuelve de
   vuelta a la misma IP (FCrDNS correcto). La IP del NAS (`79.117.52.185`, PTR
   genérico de Digi) es solo el hop interno de autenticación hacia el relay de
   Hostalia; Gmail no lo evalúa. Sin acción posible ni necesaria.

3. **List-Unsubscribe / acortadores / ratio imagen-texto** aplican a envío
   **masivo/marketing**, no a un buzón personal. El mensaje de prueba era texto
   plano sin enlaces, imágenes ni HTML → ninguna de esas penalizaciones aplica.
   ✅ **Actualización (2026-09-22):** el correo pasará a usarse como *newsletter*,
   así que ya se ha creado la solución autohospedada con baja en un clic:
   ver [`newsletter/README.md`](newsletter/README.md).


