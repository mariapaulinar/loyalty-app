# 06 — Paridad PWA → nativo (componentes y comportamiento)

Fuente de verdad visual/estructural: Blade en `loyalty-app/resources/views/`.  
Las apps nativas **no** embeben HTML; replican layout, jerarquía, CTAs, estados y semántica de color del **PWA de producción**.

## Principios de fidelidad

1. **Misma información, mismo orden** que el Blade de la pantalla.
2. **Mismos estados:** loading (skeleton), empty, error, success toast.
3. **Misma semántica de color:** chrome = branding API; cara de card = partner; CTAs fuertes PWA usan **accent** (`pwa_theme_color` / accent tokens).
4. **UI sans; códigos en mono.**
5. **No inventar** bottom TabBar de 4 ítems, tab QR global, ni dashboards que la PWA no tenga.
6. Pixel-perfect CSS no es el objetivo; **paridad de producto** sí.

## Layouts → shell nativo

| PWA | Nativo |
|-----|--------|
| `member/layouts/default.blade.php` — **top header** (logo, My Cards, avatar) | Top bar equivalente; opcional bottom **Home \| My Cards** solamente |
| QR en `card/index`, `stamp-card/index`, `voucher/index` | Modal/sheet QR **desde detalle**, payload = URL staff de acción |
| `staff/layouts/default.blade.php` | Home hub + CTA Scan; stacks de acción |
| `<x-ui.brand-styles />` | ThemeStore + `GET mobile/branding` |
| First-visit loader + timezone cookie | `M_Splash`: capturar TZ del device; no bloquear eternamente |

## UI kit compartido (`components/ui/`)

| Blade | Nativo | Notas |
|-------|--------|-------|
| `ui/button` | Primary / Accent / Secondary / Destructive | CTAs “Get started / Access / Show QR” → **accent** como PWA |
| `ui/input` | TextField | Errores 422 |
| `ui/toast` | Snackbar | Post earn/redeem |
| `ui/empty-state` | EmptyState | |
| `ui/skeleton` | Shimmer | Home / My Cards |
| `ui/qr-modal*` | Full-screen QR | Desde detalle de programa |
| `ui/progress-bar` | Progress | Stamps |
| `ui/tabs` | Segmented | Detalle Rewards/History/Rules; My Cards secciones |
| `ui/pin-input` | Digits | Enter code |
| `ui/rules-section` | Rules block | |

## Member — dominio

| Blade | Screen(s) | Comportamiento mínimo |
|-------|-----------|----------------------|
| `premium-card` / `loyalty-card` | Home, My Cards, Detail | Cara partner; tap → detail |
| Home grids (loyalty / vouchers / stamps) | `M_Home` | Discovery multi-tipo ordenado como PWA |
| `my-cards` métricas + secciones | `M_MyCards` | Stamps → loyalty → vouchers (+ tiers si API) |
| `follow-card` | Card detail | Follow/unfollow |
| `rewards` / tabs Rewards·History·Rules | Card detail | Orden PWA |
| CTA Scan / Show QR | Card / Stamp / Voucher detail | Genera URL staff; modal QR |
| `stamp-progress` / enroll | Stamp detail | |
| `save-voucher` | Voucher detail | |
| Auth blades | Login / Register | V1 password API |
| `switch-account` | Switch | device_code |
| `code/redeem` | Enter code | Acceso desde Profile u oculto; no nav top |

**No cablear** `identity-badge` como tab: en layout PWA actual **no** está en el chrome.

## Staff — dominio

| Blade | Screen | Comportamiento |
|-------|--------|----------------|
| `auth/login` | `S_Login` | Email → club(s) → password |
| `index` | `S_Home` | Search + Scan + recent |
| `qr/scanner` | `S_Scanner` | Scan URL → navegar a acción (no sheet genérico) |
| `loyalty-cards/add` | `S_EarnPoints` | |
| `loyalty-cards/claim` | `S_RedeemReward` | |
| `stamps/add` · `claim` | Add/Redeem stamps | |
| `vouchers/redeem` | `S_VoucherRedeem` | |
| History | `S_History` | Destino post-éxito PWA |

## Checklist agente (por pantalla)

- [ ] Blade de `05-screen-map.md` abierto
- [ ] Secciones y CTAs en el mismo orden
- [ ] QR contextual si aplica (URL staff, no identity tab)
- [ ] Empty / loading / error
- [ ] Color: chrome branding vs partner vs accent CTA

## Branding

Ver [`02-design-system.md`](02-design-system.md). Paint-first + ETag; no fetch por pantalla.

## Fuera de alcance V1

Admin/Partner, GTM, SEO, Vite, referrals/request-points, staff generate-code (salvo TASK), realtime stamp-review WebSocket.
