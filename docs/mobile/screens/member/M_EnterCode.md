# M_EnterCode

**PWA:** `member/code/redeem.blade.php`  
**Nav:** ruta existe; en layout PWA actual el link de menú está **comentado** — no poner en top bar V1. Acceso desde Profile u deep link.

## UI

Campo código mono 4 dígitos, CTA accent, éxito/error.

## API

`POST /member/codes/redeem` (G-MEM-07).

## DoD

Errores API visibles; éxito actualiza balances relevantes.
