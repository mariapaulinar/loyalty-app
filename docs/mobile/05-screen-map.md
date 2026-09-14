# 05 — Screen map (PWA → nativo)

Dos apps. IDs estables por producto. Specs en `screens/member/` y `screens/staff/`.  
**Paridad de flujo:** replicar el PWA de producción (`resources/views/member|staff`), no inventar chrome de “wallet genérico”.

## App: Loyalty Member

| Screen ID | PWA referencia | Ruta web | API / notas |
|-----------|----------------|----------|-------------|
| `M_Splash` | first-visit loader + TZ | — | branding + init; capturar timezone del device |
| `M_Home` | `home.blade.php` / showcase / portal | `/{locale}/` | Discovery: loyalty + vouchers + stamps (no solo wallet) |
| `M_MyCards` | `my-cards.blade.php` | `/my-cards` | Wallet: métricas + secciones stamps → loyalty → vouchers |
| `M_CardDetail` | `card/index.blade.php` | `/card/{id}` | Detalle + **CTA Show QR** (deep-link staff earn) |
| `M_RewardDetail` | `card/reward.blade.php` | `/card/{id}/{reward}` | P1 |
| `M_RewardClaim` | `card/reward-claim.blade.php` | `.../claim` | P1 — QR claim firmado staff |
| `M_StampDetail` | `stamp-card/index.blade.php` | `/stamp-card/{id}` | Enroll + **CTA QR** stamps.add |
| `M_VoucherDetail` | `voucher/index.blade.php` | `/voucher/{id}` | Save + **CTA QR** vouchers.redeem |
| `M_EnterCode` | `code/redeem.blade.php` | `/enter-code` | Existe en PWA; **no** en nav principal (menú comentado) |
| `M_DeviceCode` | switch / identity badge (opcional) | account | `device_code` + sync; **no** es el QR de caja |
| `M_Login` | `auth/login.blade.php` | `/login` | Password API V1; OTP web = P1 |
| `M_Register` | `auth/register.blade.php` | `/register` | Upgrade anónimo / register |
| `M_SwitchAccount` | `account/switch-account.blade.php` | switch | `session/switch` + `issue_token` |
| `M_Profile` | manage / privacy | `/manage/*` | Account; logout **solo si hay email** |
| `M_Legal` | about/terms/privacy/faq/contact | content | Footer legal |

**Deprecated ID:** `M_QrIdentity` — no usar como tab. Sustituido por QR contextual en detalle + `M_DeviceCode` opcional.

### Nav Member (paridad PWA)

La PWA **no** usa bottom TabBar. Chrome autenticado:

- **Top bar:** logo → Home · **My Cards** · avatar/menú (Account, Switch, Legal, Logout si registered)
- **QR de caja:** solo desde detalle de programa (card / stamp / voucher), modal grande “Show QR to staff”
- **Enter code / referrals / request-points:** no en chrome V1 (ocultos o V1.1)

Nativo puede usar bottom bar de **2 destinos** (Home | My Cards) + entry Profile en el top/avatar si mejora el touch — **prohibido** tab QR global inventado.

### Arranque Member (Lealmi)

`M_Splash` (tema + branding) → si hay token Sanctum válido → `M_Home`; si no → `M_Login`.  
**No** se usa `POST /member/init` (modo anónimo desactivado en prod y local).

### Contrato QR Member → Staff (crítico)

El QR **no** es un UUID suelto en tab. En PWA el payload es una **URL de acción Staff**:

| Contexto | Destino conceptual (PWA route name) | Identifiers en URL |
|----------|-------------------------------------|--------------------|
| Loyalty earn | `staff.earn.points` | `member_identifier` + `card_identifier` |
| Reward claim | `staff.claim.reward` (signed) | member + card + reward |
| Stamp add | `staff.stamps.add.show` | member + stamp card |
| Stamp claim | `staff.stamps.claim.show` | member + stamp card |
| Voucher redeem | `staff.vouchers.redeem.show` | member (+ voucher) |

Nativo: generar el mismo deep-link (URL absoluta del tenant o esquema documentado en API/gaps). Staff scanner navega a la pantalla de acción correspondiente.

## App: Loyalty Staff

| Screen ID | PWA referencia | Ruta web | API / notas |
|-----------|----------------|----------|-------------|
| `S_Splash` | boot | — | branding + validate token |
| `S_Login` | `auth/login.blade.php` | `/staff/login` | email → club → password (OTP P1) |
| `S_Home` | `index.blade.php` | `/staff/` | Saludo + search + CTA Scan + recent |
| `S_Scanner` | `qr/scanner.blade.php` | `/staff/scan` | Cámara; parse URL QR → deep-link |
| `S_EarnPoints` | `loyalty-cards/add` | earn | Si QR/API trae member+card |
| `S_RedeemReward` | `loyalty-cards/claim` | claim | |
| `S_AddStamps` | `stamps/add` | stamps/add | |
| `S_RedeemStamp` | `stamps/claim` | stamps/claim | |
| `S_VoucherRedeem` | `vouchers/redeem` | vouchers | validate + redeem |
| `S_History` | history blades | history | Post-éxito PWA; P2 API |
| `S_MemberLookup` | search / fallback | — | Lookup manual si QR no es URL válida |

**Deprecated:** `S_MemberSheet` como hub genérico post-scan. En PWA el scan **aterriza directo** en earn/claim/redeem. Lookup sheet solo si el QR no resuelve o fallback manual.

### Nav Staff

Dashboard · Scan (CTA primario) · (opcional) members/recent. Entrada a stamps/vouchers casi solo vía QR/search.

### Arranque Staff

`S_Splash` → token ? `S_Home` : `S_Login`. **Sin Role Gate.**
