# Backend gaps — API vs PWA Member/Staff

Prioridad: **P0** bloquea V1 core · **P1** paridad importante · **P2** nice/V1.1

## Infra / auth / branding

| ID | Gap | Priority | Notas |
|----|-----|----------|-------|
| G-AUTH-01 | Guard `staff_api` ausente en `config/auth.php` | **P0 DONE** | Añadido Sanctum guard `staff_api`. |
| G-AUTH-02 | `POST /member/init` no emite token Sanctum | **P0 DONE (opt-in)** | Token solo con `issue_token` / `X-Issue-Token`. PWA sin cambio de forma ni writes a tokens. |
| G-AUTH-03 | OTP login/register solo en rutas web | **P0 DONE (API)** | `POST /member/login/check`, `/login/otp/send`, `/login/otp/verify` para apps nativas. Web sin cambio. |
| G-BRAND-01 | Apps sin tema white-label live | **P0 DONE** | `GET /{locale}/v1/mobile/branding` + ETag/304 + cache; invalidación en settings/logos. Fixture `mobile-branding.json`. |

## Member — faltan en REST

| ID | Capacidad PWA | Priority | Endpoint propuesto |
|----|---------------|----------|-------------------|
| G-MEM-01 | Detalle card (rewards, media, rules) | **P0 DONE** | `GET /member/cards/{cardId}` |
| G-MEM-02 | Follow / unfollow | **P0 DONE** | `POST\|DELETE /member/cards/{cardId}/follow` |
| G-MEM-03 | Detalle reward + elegibilidad | P1 | `GET /member/cards/{cardId}/rewards/{rewardId}` |
| G-MEM-04 | Claim reward (member side QR/link) | P1 | Coordinar con staff redeem; o `POST .../claim` |
| G-MEM-05 | Detalle stamp card | **P0 DONE** | `GET /member/stamp-cards/{id}` |
| G-MEM-06 | Detalle voucher | **P0 DONE** | `GET /member/vouchers/{id}` |
| G-MEM-07 | Redeem enter-code | **P0 DONE** | `POST /member/codes/redeem` |
| G-MEM-08 | Identity/QR payload | **P0 DONE** | `GET /member/identity` |
| G-MEM-09 | Discover/home feed (catálogo público) | **P1** | PWA usa `PageController` SSR; `all-cards` ≠ discovery. Nativo: Home hero + wallet CTA; catálogo completo requiere endpoint nuevo (sin cambios prod hasta staging). |
| G-MEM-10 | Profile update | P1 | `PUT /member/` o `/member/profile` |
| G-MEM-11 | Privacy download/delete | P1 | Endpoints privacy |
| G-MEM-12 | Claim voucher batch | P2 | `POST /member/vouchers/claim/{batch}/{token}` |
| G-MEM-13 | Referrals | P2 | V1.1 |
| G-MEM-14 | Request/send points | P2 | V1.1 |
| G-MEM-16 | Payload QR deep-link staff | **P0 docs DONE** | `fixtures/member-qr-deep-links.json` + `05-screen-map.md` |

## Staff — parcial

| ID | Capacidad PWA | Priority | Notas |
|----|---------------|----------|-------|
| G-STAFF-01 | Historial transacciones | P2 | Lookup puede incluir resumen; historial dedicado opcional |
| G-STAFF-02 | Generate point code | P2 | Web `generate-code` |
| G-STAFF-03 | Search members (texto) | P1 | Web `api/search-members`; scanner usa identifier exacto |
| G-STAFF-04 | Documentar body de purchase/stamps/redeem | **P0** | Asegurar OpenAPI + fixtures alineados a `StaffController` |

## Criterio de cierre P0

Antes de F4/F5 completos:

1. `staff_api` configurado y login staff E2E.
2. Init anónimo retorna token usable en `auth:member_api` (con `issue_token`).
3. Card detail + follow, stamp detail, voucher detail, identity, redeem code disponibles y en OpenAPI.
4. Branding público disponible y documentado.
5. Fixtures actualizados en `docs/mobile/fixtures/`.

## Referencias código

- `routes/api.php` (member/staff groups)
- `app/Http/Controllers/Api/StaffController.php`
- `app/Http/Controllers/Member/CardController.php` (web follow/claim)
- `app/Http/Controllers/Member/CodeController.php`
