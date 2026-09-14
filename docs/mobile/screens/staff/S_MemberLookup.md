# S_MemberLookup

**PWA:** búsqueda en `staff/index` + fallback manual (no es el post-scan feliz).

## UI

Resultado de search/lookup: nombre member, programas del club, atajos a earn/stamps/voucher **si** el staff eligió desde search (no desde QR de acción).

## API

`GET /staff/member/{identifier}` (+ search P1 `G-STAFF-03` cuando exista).

## DoD

Solo para fallback/search. El happy path del scanner **no** pasa por aquí si el QR es URL de acción.

---

### Nota migración

`S_MemberSheet` como hub obligatorio post-scan queda **deprecado** respecto a la PWA.
