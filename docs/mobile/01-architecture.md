# 01 — Architecture

## Dos apps independientes

| App | Repos | applicationId / bundle |
|-----|-------|-------------------------|
| **Loyalty Member** | `loyalty-member-android`, `loyalty-member-ios` | `com.lealmi.loyalty.member` |
| **Loyalty Staff** | `loyalty-staff-android`, `loyalty-staff-ios` | `com.lealmi.loyalty.staff` |

Specs y OpenAPI viven en `loyalty-app` (`docs/mobile/`). No hay SDK multiplataforma compartido: contrato = OpenAPI + docs + branding API + `design-tokens.json` (fallback).

**No hay Role Gate.** Cada binary es solo Member o solo Staff (stores separados, onboarding separado).

```
┌────────────────────────┐     Bearer Sanctum      ┌──────────────────────────┐
│ loyalty-member-android │ ─── /member/... ──────► │                          │
│ loyalty-member-ios     │                         │ loyalty-app              │
└────────────────────────┘     GET /mobile/branding│ /api/{locale}/v1         │
┌────────────────────────┐     (público, cache)    │                          │
│ loyalty-staff-android  │ ─── /staff/... ───────► │                          │
│ loyalty-staff-ios      │                         └──────────────────────────┘
└────────────────────────┘
```

Ambas apps llaman **también** `GET /mobile/branding` (paint-first, ETag) para white-label; ver [`02-design-system.md`](02-design-system.md).

Legacy folder names `loyalty-android` / `loyalty-ios` (si existen) se tratan como **Member**; Staff usa los repos `loyalty-staff-*`.

## Capas recomendadas (cada app)

1. **UI** — Compose / SwiftUI (`screens/member/*` o `screens/staff/*`) + ThemeStore.
2. **Presentation** — ViewModels / `@Observable`.
3. **Domain** — use cases de esa app únicamente.
4. **Data** — API client + secure storage (+ QR offline solo Member) + branding disk cache.

### Android stack

- Kotlin 2.x, minSdk 26+, Compose, Navigation, Hilt, Retrofit + OkHttp, Moshi
- EncryptedSharedPreferences / DataStore
- **Staff only:** CameraX + ML Kit Barcode
- Coil

### iOS stack

- iOS 16+, SwiftUI, NavigationStack, URLSession, Keychain
- **Staff only:** AVFoundation / DataScanner

## Locale

- Path: `en-us`, `es-es` (kebab).
- Mapear `en_US` ↔ `en-us` si el perfil lo requiere.

## Offline

- **Member:** cachear identity QR (unique_identifier / device_code) cifrado.
- **Staff:** requiere red para POS; sin mock de canjes offline en V1.

## Config

```
API_BASE_URL=https://{host}
DEFAULT_LOCALE=en-us
```

Base: `{API_BASE_URL}/api/{locale}/v1`

## Seguridad

- TLS only; no logs de tokens; Staff no persiste passwords.
- Logout revoca token vía API cuando hay red.

## Realtime

Reverb/Echo **no** requerido en V1.
