# S_Scanner

**PWA:** `staff/qr/scanner.blade.php` + `resources/js/qrscanner.js`

## UI

Camera fullscreen, marco, permiso denegado → settings. Fallback: pegar/escribir identifier o URL.

## Comportamiento (paridad PWA)

1. Decode del QR.
2. Si es **URL same-tenant staff** (earn / claim / stamps / vouchers) → **navegar directo** a la pantalla de acción (`S_EarnPoints`, `S_RedeemReward`, `S_AddStamps`, `S_RedeemStamp`, `S_VoucherRedeem`) parseando path params.
3. Si es solo identifier / URL inválida → `S_MemberLookup` o input manual + lookup API.
4. No abrir un hub genérico “elige acción” cuando el QR ya trae la acción (así funciona la PWA).

## API

Lookup `GET /staff/member/{identifier}` solo en fallback. Acciones usan endpoints staff del contrato.

## DoD

QR de card Member (URL earn) aterriza en earn con member+card correctos.
