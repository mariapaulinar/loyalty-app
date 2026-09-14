# 03 — API contract

## Consumidores

Dos apps nativas independientes consumen este contrato:

- **Loyalty Member** → solo rutas `/member/...`
- **Loyalty Staff** → solo rutas `/staff/...`

El PWA web sigue usando sobre todo rutas Blade; no debe verse afectado por clientes nativos.

## Base

```
{API_BASE_URL}/api/{locale}/v1
```

Ejemplo: `https://example.com/api/en-us/v1/member/init`

OpenAPI canónico: `storage/api-docs/api-docs.json` (UI: `/api/documentation`).

**Nota:** el PWA JS a veces llama `/{locale}/api/v1/...`; Laravel monta `/api/{locale}/v1/...`. Las apps nativas usan **solo** el mount Laravel correcto.

## Headers

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}   # rutas protegidas
```

## Auth scheme

| Rol | Guard | Cómo se obtiene el token |
|-----|-------|--------------------------|
| Member | `member_api` (Sanctum) | `POST .../member/login` o `register` → token; anónimo vía `init` (ver auth doc — puede requerir token post-init según implementación) |
| Staff | `staff_api` (Sanctum) | `POST .../staff/login` con `email`, `password`, `club_id` |

## Shared — branding (público)

| Method | Path | Auth | Uso |
|--------|------|------|-----|
| GET | `/mobile/branding` | no | White-label theme: `brand_color`, palette `primary.50–950`, logos, `app_name`, PWA theme/background |

**Comportamiento:**

- Cache servidor ~1h (`mobile.branding.payload.v1`); invalidación al cambiar settings/logos admin.
- Response headers: `ETag`, `Cache-Control: public, max-age=3600`.
- Request opcional: `If-None-Match: <etag>` → **304** sin body.
- Fixture: [`fixtures/mobile-branding.json`](fixtures/mobile-branding.json).
- **No** incluye colores de partner/card: esos vienen en payloads de cards.
- Clientes: paint-first + cache local; ver [`02-design-system.md`](02-design-system.md).

## Member — endpoints existentes

| Method | Path | Auth | Uso |
|--------|------|------|-----|
| POST | `/member/login` | no | Login password → `{ token }` |
| POST | `/member/login/check` | no | Paso 1 PWA → `{ exists, has_password, email }` |
| POST | `/member/login/otp/send` | no | Enviar OTP login → `{ success, message, cooldown_seconds? }` |
| POST | `/member/login/otp/verify` | no | Verificar OTP → `{ success, token }` |
| POST | `/member/register` | no | Registro |
| POST | `/member/init` | no | Sesión anónima por `device_uuid`. Token Sanctum **solo** si `issue_token: true` o header `X-Issue-Token: 1` |
| GET | `/member/session` | no* | Estado sesión dispositivo |
| POST | `/member/session/switch` | no | Switch por device code. Token solo con `issue_token` / `X-Issue-Token` (igual que init) |
| POST | `/member/session/link-email` | no | Vincular email |
| GET | `/member/` | yes | Perfil |
| GET | `/member/identity` | yes | Payload QR (unique_identifier, device_code, …) |
| POST | `/member/logout` | yes | Logout |
| GET | `/member/all-cards` | yes | Cards disponibles |
| GET | `/member/followed-cards` | yes | Seguidas |
| GET | `/member/transacted-cards` | yes | Con transacciones |
| GET | `/member/balance/{cardId}` | yes | Balance |
| GET | `/member/cards/{cardId}` | yes | Detalle card + rewards + is_followed |
| POST/DELETE | `/member/cards/{cardId}/follow` | yes | Follow/unfollow |
| GET | `/member/stamp-cards` | yes | Stamp cards |
| GET | `/member/stamp-cards/{id}/history` | yes | Historial stamps |
| GET | `/member/stamp-cards/{stampCardId}` | yes | Detalle stamp + enrollment |
| GET | `/member/my-stamp-cards` | yes | Enrolled |
| POST/DELETE | `/member/stamp-cards/{id}/enroll` | yes | Enroll/unenroll |
| GET | `/member/my-vouchers` | yes | Saved vouchers |
| GET | `/member/vouchers/{id}` | yes | Detalle voucher + is_saved |
| POST/DELETE | `/member/vouchers/{id}/save` | yes | Save/unsave |
| POST | `/member/codes/redeem` | yes | Canje código 4 dígitos |

\*Ver implementación `AnonymousMemberController`.

## Staff — endpoints existentes

| Method | Path | Auth | Uso |
|--------|------|------|-----|
| POST | `/staff/login` | no | Login (`email`, `password`, `club_id`) |
| GET | `/staff/` | yes | Perfil staff |
| POST | `/staff/logout` | yes | Logout |
| GET | `/staff/member/{identifier}` | yes | Lookup member |
| POST | `/staff/cards/{cardId}/purchase` | yes | Compra / puntos |
| POST | `/staff/cards/{cardId}/rewards/{rewardId}/redeem` | yes | Canje reward |
| POST | `/staff/stamp-cards/{stampCardId}/stamps` | yes | Añadir stamps |
| POST | `/staff/stamp-cards/{stampCardId}/redeem` | yes | Canje stamp reward |
| POST | `/staff/vouchers/validate` | yes | Validar voucher |
| POST | `/staff/vouchers/{voucherId}/redeem` | yes | Canjear voucher |

## Errores

- `401` credenciales / token inválido → re-auth.
- `403` sin permiso.
- `404` recurso no encontrado.
- `422` validación (`message` + `errors`).
- `400` negocio (ej. anonymous disabled → `requires_login: true`).

## Gaps

Ver [`backend-gaps.md`](backend-gaps.md). No llamar paths no listados aquí hasta que existan en `routes/api.php` + OpenAPI.

## Fixtures

Ejemplos en [`fixtures/`](fixtures/).
