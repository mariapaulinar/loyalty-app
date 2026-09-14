# 07 — Navigation shell (PWA chrome → nativo)

Referencia Blade: `resources/views/member/layouts/default.blade.php` (Member) y `staff/layouts/default.blade.php` (Staff).

## Member — chrome autenticado

La PWA **no** usa bottom TabBar de 4 ítems. Replica:

| Zona PWA | Nativo |
|----------|--------|
| Logo → Home (`member.index`) | Tap logo / título → `M_Home` |
| Pill/icono **My Cards** (`member.cards`) | Acción en top bar → `M_MyCards` |
| Avatar dropdown | `M_Profile` (Account, Switch, Legal, Logout si email) |
| QR en detalle de programa | Modal desde `M_CardDetail` / stamp / voucher — **no** tab QR |

Opcional nativo: bottom bar **solo** `Home | My Cards` (2 destinos) si mejora el pulgar; Profile siempre desde avatar/menú.

### Home (`M_Home`) vs My Cards (`M_MyCards`)

| Pantalla | Rol PWA | Contenido |
|----------|---------|-----------|
| **Home** | `member/home.blade.php` | Hero + CTA wallet; secciones discovery (loyalty, vouchers, stamps). *API discovery completo = G-MEM-09; wallet API no sustituye catálogo público.* |
| **My Cards** | `member/my-cards.blade.php` | Wallet: métricas + stamps → loyalty → vouchers |

### Logout (PWA)

Solo visible si el member tiene **email** (`@if(auth('member')->user()?->email)`). Anónimos: Switch account, no logout clásico.

## Staff — chrome

| Zona PWA | Nativo |
|----------|--------|
| Dashboard (`staff.index`) | `S_Home`: saludo + search + **Scan** CTA |
| Scan (`staff.qr.scanner`) | `S_Scanner` |
| Sidebar / drawer ops | Earn/redeem vía **deep-link del QR**, no hub genérico |

Post-scan feliz: URL del QR member → pantalla de acción (`S_EarnPoints`, etc.). `S_MemberLookup` solo fallback.

## Dark mode

PWA: `localStorage color-theme` + `dark:` Tailwind. Nativo V1: respetar `isSystemInDarkTheme()`; logos `logo_dark_url` desde branding cuando existan.

## Implementación por repo (estado real — ver `08-implementation-status.md`)

| Repo | Shell | Estado |
|------|-------|--------|
| `loyalty-member-android` | `MemberAppShell` + Home / MyCards / Profile | ⚠️ V1 parcial — auth, wallet, detalle card/stamp/voucher + QR |
| `loyalty-member-ios` | `MemberAppShell` SwiftUI | ❌ scaffold visual; sin API |
| `loyalty-staff-android` | Hub + Scan CTA placeholder | ❌ login/scanner placeholder |
| `loyalty-staff-ios` | Placeholder | ❌ |

### Menú avatar (paridad PWA autenticado)

- Home, Mis tarjetas (duplicado móvil PWA)
- **Mi cuenta** → `M_Profile`
- **Cerrar sesión** → solo si `email` presente
- **No** incluir: Switch account, Enter code, Referrals (van en perfil u ocultos V1)
