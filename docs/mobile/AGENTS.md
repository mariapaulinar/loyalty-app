# AGENTS.md — Reglas globales para agentes

## Rol

Implementas **una** de estas apps nativas (nunca ambas en la misma TASK):

- **Loyalty Member** — `loyalty-member-android` / `loyalty-member-ios`
- **Loyalty Staff** — `loyalty-staff-android` / `loyalty-staff-ios`

o **backend Laravel**, según la TASK. El PWA Blade es la referencia de paridad UX.

## Fuente de verdad

Este pack `docs/mobile/` es el contrato para **cualquier** chat/agente/modelo. Léelo antes de generar UI o clientes HTTP.

Orden de lectura típico:

1. Esta hoja + TASK asignada  
2. [`00-product-context.md`](00-product-context.md) · [`01-architecture.md`](01-architecture.md)  
3. [`03-api-contract.md`](03-api-contract.md) · [`04-auth-and-sessions.md`](04-auth-and-sessions.md)  
4. [`02-design-system.md`](02-design-system.md) · [`06-pwa-component-parity.md`](06-pwa-component-parity.md) · [`07-navigation-shell.md`](07-navigation-shell.md)  
5. Screen/flow de la TASK (`screens/`, `flows/`)

## Obligatorio

1. Leer la TASK completa; respetar campo **App** (`member` | `staff` | `backend`).
2. Usar solo endpoints del contrato (`03-api-contract.md` / OpenAPI / `routes/api.php`).
3. Paridad con vistas PWA de **ese** rol (`resources/views/member|staff`) + [`05-screen-map.md`](05-screen-map.md) + [`06-pwa-component-parity.md`](06-pwa-component-parity.md).
4. Tema: fallback [`design-tokens.json`](design-tokens.json); runtime `GET /mobile/branding` paint-first ([`02-design-system.md`](02-design-system.md)).
5. Locale en path: `/api/{locale}/v1/...`. `Accept: application/json`.
6. Auth Sanctum Bearer; storage seguro; nunca logs de tokens.
7. **Member:** `POST /member/init` (y `session/switch`) con `issue_token: true` (o `X-Issue-Token: 1`).
8. **No Role Gate** — no selector Member/Staff en un binary.
9. i18n alineada a `lang/` (`en_US` / `es_ES` mínimo).
10. Una TASK = un PR lógico en **un** repo.
11. No bloquear Home/POS esperando branding.
12. **Member chrome:** Home + My Cards (+ Profile/account); **QR solo en detalle de programa** (URL staff). No TabBar con tab QR universal.
13. **Staff scan:** seguir deep-link del QR member hacia la pantalla de acción (earn/claim/redeem), no inventar hub genérico si el QR ya trae la ruta.

## Prohibido

- Inventar endpoints o auth schemes.
- Meter pantallas Staff en la app Member (o al revés).
- Agent API (`X-Agent-Key`) como transporte de las apps.
- Scope Partner/Admin.
- Cambiar el PWA web salvo TASK backend.
- Hardcodear `APP_URL`; secrets en repo.
- Tratar la paleta estática de `design-tokens.json` como colores de producción white-label.
- Llamar `/mobile/branding` en cada navegación de pantalla o en loops de cards.
- Inventar bottom TabBar `Home | My Cards | QR | Profile` o QR de identidad como tab principal.

## Si la API no alcanza

Actualizar `backend-gaps.md`; no fingir éxito de producción.

## Paths frecuentes

```
loyalty-app/docs/mobile/
loyalty-app/routes/api.php
loyalty-app/storage/api-docs/api-docs.json
loyalty-member-android/ | loyalty-member-ios/
loyalty-staff-android/  | loyalty-staff-ios/
```
