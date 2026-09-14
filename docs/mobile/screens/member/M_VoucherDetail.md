# M_VoucherDetail

**PWA:** `member/voucher/index.blade.php`  
**Ruta:** `/voucher/{id}`

## UI PWA

1. QR canje staff
2. Código copiable
3. Tabs: Detalles · Historial · Reglas
4. Save / unsave en wallet

## API

- `GET /member/vouchers/{id}`
- `POST|DELETE /member/vouchers/{id}/save`

## Nativo V1 (Member Android)

- Detalle + código + QR redeem + save/unsave
- Pendiente: tabs, copy-to-clipboard UX PWA

## DoD

QR → `staff.vouchers.redeem.show`; save refleja en My Cards.
