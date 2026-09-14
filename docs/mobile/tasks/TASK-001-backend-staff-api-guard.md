# TASK-001 — Fix staff_api Sanctum guard

**Platform:** backend  
**Phase:** F1  
**depends_on:** none  
**Gaps:** G-AUTH-01

## Objetivo
Habilitar `auth:staff_api` añadiendo guard Sanctum en `config/auth.php` (espejo de `member_api`/`partner_api`) con provider `staff`.

## Contexto obligatorio
- `config/auth.php`
- `routes/api.php` (grupo staff)
- `app/Models/Staff.php` (HasApiTokens)

## Pasos
1. Añadir guard `staff_api` driver sanctum, provider staff, hash false.
2. Verificar Staff usa `Laravel\Sanctum\HasApiTokens`.
3. Probar login staff + GET `/staff/` con Bearer (manual o test Pest).

## Definition of Done
- Guard existe; request autenticada no retorna 500 por guard indefinido.
- Documentar en `03-api-contract.md` si aplica.

## No hacer
Cambiar lógica de login staff más allá de lo necesario.

