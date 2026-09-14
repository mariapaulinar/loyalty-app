# 08 — Auditoría PWA ↔ nativo (estado de sincronización)

**Última revisión:** barrido completo de `routes/web.php`, layouts Blade Member/Staff, y repos nativos.

Este documento es la **fuente de verdad del gap actual**. Los specs de pantalla (`screens/`) y `05-screen-map.md` describen el **objetivo**; aquí se marca **qué está hecho**.

## Reglas de paridad (no negociables)

1. **Mismo flujo** que el PWA de producción — no inventar TabBar de 4 ítems ni tab QR global.
2. **Mismo orden** de secciones y CTAs por pantalla (ver `06-pwa-component-parity.md`).
3. **Copy** desde `lang/es_ES` (locale app `es-es`).
4. **QR contextual** en detalle card/stamp/voucher → URL staff (`fixtures/member-qr-deep-links.json`).
5. **Logout** solo si member tiene email (cuenta registrada).
6. **Home** = discovery; **My Cards** = wallet (orden PWA: stamps → loyalty → vouchers).

## Member — inventario PWA → nativo

| Screen ID | PWA (Blade / ruta) | Member Android | Member iOS | Staff A/I |
|-----------|-------------------|----------------|------------|-----------|
| `M_Splash` | first-visit loader + TZ | ✅ init + branding | ⚠️ sleep placeholder | ⚠️ placeholder |
| `M_Home` | `home.blade.php` `/` | ⚠️ hero + CTAs; sin catálogo G-MEM-09 | ⚠️ hero estático | — |
| `M_MyCards` | `my-cards.blade.php` | ⚠️ wallet API + greeting; sin tiers/métricas completas | ❌ | — |
| `M_CardDetail` | `card/index` | ⚠️ balance + QR + follow + tabs rewards/rules; historial sin API | ❌ | — |
| `M_StampDetail` | `stamp-card/index` | ⚠️ QR + enroll + tabs progreso/historial/reglas | ❌ | — |
| `M_VoucherDetail` | `voucher/index` | ⚠️ QR + save + tabs detalles/reglas; historial sin API | ❌ | — |
| `M_RewardDetail` | `card/reward` | ❌ P1 | ❌ | — |
| `M_RewardClaim` | `card/reward-claim` | ❌ P1 | ❌ | — |
| `M_Login` | `auth/login` multi-step | ⚠️ email→check→password/OTP→verify; requiere deploy API OTP en prod | ❌ | — |
| `M_Register` | `auth/register` + OTP web | ✅ form sin password + OTP verify | ❌ | — |
| `M_SwitchAccount` | `account/switch-account` | ➖ N/A Lealmi (sin modo anónimo) | ❌ | — |
| `M_Profile` | `manage/account` tabs | ⚠️ Perfil / Localización / Privacidad | ⚠️ placeholder | — |
| `M_EnterCode` | `code/redeem` (no nav) | ✅ desde perfil | ❌ | — |
| `M_Legal` | footer content | ✅ WebView (terms/privacy/faq/about/contact) | ❌ | — |
| `S_Home` | `staff/index` | — | — | ⚠️ UI sin API |
| `S_Login` | `staff/auth/login` email→club→password | — | — | ❌ placeholder |
| `S_Scanner` | `staff/qr/scanner` | — | — | ❌ placeholder |
| `S_EarnPoints` … `S_VoucherRedeem` | deep-link post-scan | — | — | ❌ |

Leyenda: ✅ paridad V1 usable · ⚠️ parcial · ❌ pendiente

## Chrome / navegación Member

| Elemento PWA | Spec | Android actual |
|--------------|------|----------------|
| Logo → Home | `07-navigation-shell.md` | ✅ wordmark + home icon |
| My Cards pill/icon | idem | ✅ wallet icon top + bottom tab |
| Avatar → Mi cuenta | idem | ✅ menú (Home, Mis tarjetas, Mi cuenta) |
| Logout si email | idem | ✅ |
| Switch en menú header | **No** | ✅ no existe |
| Sign in en menú header | **No** (hero / perfil) | ✅ eliminado del menú |
| Bottom bar 2 tabs | opcional nativo | ✅ Home \| Mis tarjetas |
| Tab QR global | **Prohibido** | ✅ no existe |

## Auth — pasos PWA vs nativo

| Paso PWA | API web | Nativo V1 |
|----------|---------|-----------|
| Email + Continuar | `POST login/check` | ✅ + redirect a registro si no existe |
| Password / OTP | web + session | ✅ password + OTP send/verify |
| OTP 6 dígitos | `login/otp/*` | ✅ (API en repo; deploy prod pendiente) |
| Registro + OTP | `register/otp/*` | ✅ start + resend + verify |
| Switch email OTP | `account/login-email/*` | ✅ (auth bearer) |
| Staff club step | `login/check` clubs[] | ❌ pendiente Staff |

## Gaps API que bloquean paridad visual

| ID | Impacto UX | Workaround nativo actual |
|----|----------|--------------------------|
| G-MEM-09 | Home sin grids discovery | Hero + nota + CTA wallet |
| G-AUTH-03 | OTP REST | ✅ en repo; probar en **local** `loyalty-app.test` (sin staging) |
| G-MEM-03/04 | Sin reward detail/claim | No pantallas reward |
| G-MEM-10/11 | Perfil sin edición/GDPR | Profile read-only |

## Próximos pasos (orden recomendado)

1. **Probar auth OTP** contra `https://loyalty-app.test` (apps debug ya apuntan ahí).
2. **Member Android:** Home discovery G-MEM-09, reward detail/claim, historial card/voucher API.
3. **Member iOS:** cablear init/auth/wallet igual que Android.
4. **Staff Android/iOS:** login real + scanner ZXing + routing deep-link.
5. **Backend staging:** G-MEM-09 discovery cuando haya ambiente seguro.

## Referencias Blade obligatorias por agente

Antes de implementar una pantalla, abrir el Blade listado en `05-screen-map.md` y contrastar con este documento.
