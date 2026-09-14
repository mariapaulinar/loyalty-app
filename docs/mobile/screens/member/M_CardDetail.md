# M_CardDetail

**PWA:** `member/card/index.blade.php`  
**Ruta:** `/card/{id}`

## UI PWA

1. Premium card + balance
2. CTA **Escanear / Mostrar QR** → `staff.earn.points`
3. Tabs: Recompensas · Historial · Reglas
4. Follow / unfollow

## API

- `GET /member/cards/{id}`
- `POST|DELETE /member/cards/{id}/follow`

## Nativo V1 (Member Android)

- Balance + QR earn modal + follow/unfollow
- Pendiente: tabs rewards/history/rules, reward navigation (P1)

## DoD

QR payload = `{origin}/{locale}/staff/earn/{member_uid}/{card_uid}`.
