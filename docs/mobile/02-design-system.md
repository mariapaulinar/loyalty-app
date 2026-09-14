# 02 — Design system & live branding

## Two layers of color

| Layer | Source | Used for |
|-------|--------|----------|
| **A. App chrome (white-label)** | Admin `brand_color` + PWA theme settings via `GET /mobile/branding` | Nav, buttons primary, splash, logos |
| **B. Partner / card accents** | `partner.brand_color` (and media) on card payloads | Loyalty card face, business accents |

**Do not** hardcode azul genérico del theme Tailwind local. Los valores en [`design-tokens.json`](design-tokens.json) son el **fallback Lealmi producción** (marca/PWA `#FCD34D`, fondo `#FFFFFF`, `foreground_on_brand` negro) hasta que llegue `GET /mobile/branding`.

PWA equivalent: [`resources/views/components/ui/brand-styles.blade.php`](../../resources/views/components/ui/brand-styles.blade.php) injects `--color-primary-*` from `ColorHelper::generatePalette(brand_color)`. Native apps must apply the same `primary` map from the branding API.

### Defaults Lealmi (admin Marca + PWA)

| Setting | Valor |
|---------|--------|
| `app_name` / short | `Lealmi` |
| `brand_color` | `#FCD34D` |
| `pwa_theme_color` | `#FCD34D` |
| `pwa_background_color` | `#FFFFFF` |
| `pwa_description` | Todas tus recompensas, un solo lugar |
| Logos | light/dark LEAL + Mi en círculo (media admin `app_logo` / `app_logo_dark`) |

## Branding API (required for theme sync)

```
GET {API_BASE_URL}/api/{locale}/v1/mobile/branding
```

- **Public**, additive; does not change PWA behavior.
- Response: see [`fixtures/mobile-branding.json`](fixtures/mobile-branding.json).
- Headers: `ETag`, `Cache-Control: public, max-age=3600`.
- Client may send `If-None-Match: <etag>` → **304** if unchanged.

Server caches payload (`mobile.branding.payload.v1`, 1h); invalidated when admin updates `brand_color` / PWA colors / logos.

## Client performance rules (paint-first)

Mirror the PWA (theme in layout head, not per-widget fetch):

1. **Cold start:** apply embedded defaults OR last disk cache → paint Splash immediately.
2. **Parallel** with member `init` / staff session restore: `GET mobile/branding` (conditional).
3. On **200**: persist JSON + ETag; update theme if `version` changed.
4. On **304** / network error: keep cache; **never block** wallet/POS.
5. Revalidate on app resume or every 1–24h — **not** on every screen navigation.
6. Do **not** call branding inside card list/detail loops.

```text
Splash (defaults/cache) → Home usable
         ↘ async branding refresh → re-tint if needed
```

## Semantic mapping

| Token / field | UI use |
|---------------|--------|
| `primary.500` / `brand_color` (`#FCD34D`) | Brand fills, nav selected, trust |
| `pwa_theme_color` / accent (`#FCD34D` en Lealmi) | CTAs fuertes (mismo oro en prod) |
| `foreground_on_brand` (`#000000` sobre oro) | Texto/iconos sobre fills de marca |
| `pwa_background_color` (`#FFFFFF`) | Splash / window background |
| `logo_url` / `logo_dark_url` | Splash / nav logo |
| Partner `brand_color` on card | That card’s face only |

Static secondary/success/warning/error neutrals may stay from [`design-tokens.json`](design-tokens.json) unless product later exposes them in settings.

## Dark mode (paridad PWA)

PWA persiste `color-theme` en `localStorage` y aplica clase `dark` en `<html>`. Nativo:

- Preferir `isSystemInDarkTheme()` en Compose/SwiftUI V1.
- Fondos: `secondary.950` / texto claro en dark; splash usa `logo_dark_url` si branding lo trae.
- No inventar paleta distinta; primary sigue viniendo de branding API.

## Typography & motion

- Sans for UI; mono for device codes / redeem codes (PWA uses mono for codes).
- Soft card elevation; no purple AI themes, neon glows, dense dashboards.
- Motion: list stagger, success toast, scanner→sheet (Staff).

## High-fidelity parity mandate

Native UIs must **replicate PWA screens and components** using Blade as the structural/behavioral source of truth (layouts, sections, CTAs, empty/loading/error). See [`06-pwa-component-parity.md`](06-pwa-component-parity.md).

Apps are additive to production PWA — prefer accuracy over reinventing UX.
