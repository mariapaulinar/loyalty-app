# Acceptance checklist — V1 (apps separadas)

Marcar por plataforma: **MA** = Member Android · **MI** = Member iOS · **SA** = Staff Android · **SI** = Staff iOS.

Estado de referencia: [`08-implementation-status.md`](08-implementation-status.md).

## Loyalty Member

### Build
- [ ] MA [ ] MI Compila release/debug
- [ ] MA [ ] MI `API_BASE_URL` + locale configurables
- [ ] MA [ ] MI Tema: fallback + `GET /mobile/branding` (paint-first, ETag)
- [ ] MA [ ] MI Sin Role Gate / sin pantallas Staff

### Navegación / chrome (PWA)
- [x] MA [ ] MI Top bar: logo, My Cards, menú Mi cuenta
- [x] MA [ ] MI Bottom opcional: Home | Mis tarjetas (sin tab QR)
- [x] MA [ ] MI Logout solo con email
- [x] MA [ ] MI Switch account **no** en menú header (solo perfil anónimo)

### Pantallas core
- [x] MA [ ] MI Splash → init `issue_token:true`
- [ ] MA [ ] MI Home discovery completo (G-MEM-09) — hero parcial OK
- [x] MA [ ] MI My Cards wallet API (stamps → loyalty → vouchers)
- [x] MA [ ] MI Detalle loyalty + QR earn + follow
- [x] MA [ ] MI Detalle stamp + QR add/claim + enroll
- [x] MA [ ] MI Detalle voucher + QR redeem + save
- [x] MA [ ] MI Login email → password (OTP P1)
- [ ] MA [ ] MI Register paridad OTP web (password API V1)
- [x] MA [ ] MI Switch por device code

### Pendiente P1+
- [ ] Reward detail/claim · Enter code · Legal · Perfil editable · OTP REST

## Loyalty Staff

### Funcional
- [ ] SA [ ] SI Login email → club → password
- [ ] SA [ ] SI Home: search + Scan + recent
- [ ] SA [ ] SI Scanner → deep-link acción (earn/claim/redeem)
- [ ] SA [ ] SI Operaciones POS vía API

## Paridad transversal
- [ ] MA MI SA SI Copy español alineado a `lang/es_ES`
- [ ] MA MI SA SI CTAs accent `#FCD34D` como PWA
- [ ] MA MI SA SI Errores 422/401 usables

## No bloquea V1
Referrals, request-points, OTP API completo, historial staff rico, stamp-review WebSocket, pinning TLS.
