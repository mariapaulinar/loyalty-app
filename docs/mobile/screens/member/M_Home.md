# M_Home

**PWA:** `resources/views/member/home.blade.php` (+ `home-showcase`, `home-portal` según setting `homepage_layout`)  
**Nav:** destino Home (top bar / bottom Home)

## Rol en el producto

**Discovery / marketplace**, no el wallet. El wallet es `M_MyCards`.

## UI (paridad directory default)

1. Hero / saludo de marketing (copy PWA; CTA hacia My Cards o Register si aplica).
2. Secciones en orden típico PWA:
   - **Loyalty cards** (primary)
   - **Vouchers** (acento violet en PWA)
   - **Stamp cards** (acento emerald en PWA)
3. Cada ítem: cara partner (`premium-card` / voucher-card / stamp-card) → tap detalle.
4. Skeleton / empty / error. Pull-to-refresh.

Si el tenant usa `showcase` o `portal`, priorizar paridad con esos blades cuando el API exponga layout (si no, directory).

## API

- **Discovery catálogo público** (loyalty + vouchers + stamps en Home PWA) = **G-MEM-09** (`PageController` SSR). `all-cards` / wallet APIs **no** son discovery.
- Nativo V1: hero + CTA **Acceder a mi wallet** → `M_MyCards`; secciones discovery cuando exista endpoint (sin cambios backend prod hasta staging).

## Acciones

Tap loyalty → `M_CardDetail` · stamp → `M_StampDetail` · voucher → `M_VoucherDetail`.

## DoD

Se siente discovery como la Home PWA (multi-tipo), no una sola lista “mis cards”.
