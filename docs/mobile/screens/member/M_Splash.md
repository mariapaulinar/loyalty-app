# M_Splash

**App:** Loyalty Member  
**PWA:** first-visit loader + timezone cookie en `member/layouts/default.blade.php`

## UI

Logo branding, fondo `pwa_background_color` / tokens, spinner breve.

## Comportamiento

1. Config (`API_BASE_URL`, locale) + capturar **timezone** IANA del device.
2. Aplicar tema paint-first (disk cache o `design-tokens.json`); **no bloquear** por red.
3. Paralelo: `GET /mobile/branding` (ETag) + auth bootstrap.
4. Token válido → `M_Home`.
5. Sin token → `POST /member/init` `{ device_uuid, issue_token: true }` → `M_Home`.
6. `requires_login` → `M_Login`.
7. Branding 200 → persistir y retintar si `version` cambió.

Sin Staff / Role Gate. Sin tab QR.

## DoD

Llega a Home discovery o Login; tema coherente; timezone guardado.
