# 00 — Product context

## Producto

**Reward Loyalty / Lealmi** (NowSquare): plataforma multi-tenant de lealtad.

```
Network → Partner → Club → Cards / StampCards / Vouchers / Staff
                         ↘ Member (wallet compartido entre clubs)
```

Backend: Laravel (`loyalty-app`). Web: PWA Blade Member + Staff (producción).

## Apps nativas V1 (separadas)

### Loyalty Member (`com.lealmi.loyalty.member`)

Cliente final con el **mismo flujo** que el PWA Member en **Lealmi** (modo anónimo desactivado):

- **Login obligatorio** (email → password u OTP → sesión)
- **Home = discovery** (loyalty + stamps + vouchers)
- **My Cards = wallet**
- QR de caja **en detalle de programa** (URL hacia Staff)
- Account / perfil / privacidad

Repos: `loyalty-member-android`, `loyalty-member-ios`.  
Referencia: `resources/views/member/`.

### Loyalty Staff (`com.lealmi.loyalty.staff`)

POS como el PWA Staff: login → hub (search + scan) → deep-link de acción desde QR member → earn/claim/redeem.

Repos: `loyalty-staff-android`, `loyalty-staff-ios`.  
Referencia: `resources/views/staff/`.

**No** Role Gate en un solo binary.

## Fuera de V1

Partner/Admin; referrals / request-points → V1.1.

## Principio de paridad

Flujo + look & feel del PWA (`05-screen-map`, `06-pwa-component-parity`), UI nativa Compose/SwiftUI.  
Branding: `GET /mobile/branding`; `design-tokens.json` = fallback.
