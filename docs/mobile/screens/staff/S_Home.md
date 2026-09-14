# S_Home

**PWA:** `staff/index.blade.php`

## UI (paridad)

1. Saludo por hora + nombre staff.
2. **Búsqueda** de members (debounce) → deep-links a historial/acciones.
3. **CTA primario Scan** → `S_Scanner`.
4. **Recent interactions** → historial / re-acción.

No hace falta grid de atajos inventados si la PWA no los tiene en hub; entrada principal = search + scan.

## API

`GET /staff/` (+ search P1 cuando exista).

## DoD

Hub usable; Scan es el CTA dominante.
