# M_StampDetail

**PWA:** `member/stamp-card/index.blade.php`  
**Ruta:** `/stamp-card/{id}`

## UI PWA

1. Breadcrumb / back
2. Stamp card visual + progreso
3. Tabs: Progreso · Historial · Reglas
4. CTA **Mostrar QR** → `staff.stamps.add.show` (o `stamps.claim` si `pending_rewards > 0`)
5. Enroll / unenroll

## API

- `GET /member/stamp-cards/{id}`
- `POST|DELETE /member/stamp-cards/{id}/enroll`

## Nativo V1 (Member Android)

- Detalle + progreso + QR modal + enroll/unenroll
- Pendiente: tabs historial/reglas, stamp-review modal (WebSocket P2)

## DoD

QR URL staff correcta; enroll sincroniza wallet.
