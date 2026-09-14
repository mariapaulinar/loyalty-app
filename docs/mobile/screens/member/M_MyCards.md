# M_MyCards

**PWA:** `resources/views/member/my-cards.blade.php`  
**Nav:** My Cards (pill / icono wallet en header PWA)

## Rol

**Wallet del member** — programas seguidos / enrolled / saved.

## UI (paridad)

1. Saludo por hora (timezone del member/device) + nombre solo si hay email.
2. **Métricas** condicionales (si hay datos): points, programs, vouchers used, stamps collected.
3. Secciones en orden PWA:
   - Stamp cards  
   - Loyalty (`premium-card`)  
   - Tiers (si API)  
   - Vouchers  
4. Empty state si wallet vacío (CTA a Home discovery).
5. Opcional V1.1: sort / hide expired (cookies PWA `wallet_sort` / `wallet_hide_expired`).

## API

`GET /member/followed-cards`, `my-stamp-cards`, `my-vouchers` (+ balances según contrato).

## Acciones

Tap → detalle correspondiente. **No** QR en esta lista (QR en detalle).

## DoD

Métricas + secciones multi-tipo; no solo tres tabs vacíos sin contenido PWA.
