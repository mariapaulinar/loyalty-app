# Desarrollo local — apps nativas ↔ `loyalty-app.test`

Sin entorno staging, las apps **debug** apuntan al backend local:

| App | Debug default | Release |
|-----|---------------|---------|
| Member Android | `https://loyalty-app.test` | `https://lealmi.com` |
| Member iOS (XcodeGen) | `https://loyalty-app.test` | configurar al publicar |

## Lealmi: sin modo anónimo

En producción y en local, **el modo anónimo está desactivado**. La app nativa:

- **No** llama a `POST /member/init` en el arranque.
- Sin token Sanctum válido → pantalla **Login**.
- Tras login/registro OTP → Home / Mis tarjetas.

## Contrato API

```
{API_BASE_URL}/api/{locale}/v1/...
```

Ejemplo login check:

```http
POST https://loyalty-app.test/api/es-es/v1/member/login/check
Content-Type: application/json

{"email":"tu@email.com"}
```

## Requisitos backend local

1. `loyalty-app` en `https://loyalty-app.test/`.
2. Endpoints auth en tu rama: `login/check`, `login/otp/*`, `register/otp/*`.
3. Cuenta de prueba o flujo registro OTP para obtener token.

## Android (emulador)

En builds **debug**, la app mapea `loyalty-app.test` → `10.0.2.2` (loopback del host) para que el emulador alcance Herd/Valet sin editar `/etc/hosts`.

Si falla HTTPS por certificado, instala el CA de mkcert/Herd en el emulador (`network_security_config` ya confía user CAs en debug).

## iOS (Simulator)

Suele resolver `loyalty-app.test` directamente (red del Mac).

## Verificación rápida

```bash
curl -sk -X POST "https://loyalty-app.test/api/es-es/v1/member/login/check" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

`POST /member/init` con `requires_login: true` es **esperado** y confirma que el modo anónimo está off; la app nativa **no** depende de ese endpoint.
