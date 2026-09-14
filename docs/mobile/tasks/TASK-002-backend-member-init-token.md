# TASK-002 — Emit Sanctum token on member init

**Platform:** backend  
**Phase:** F1  
**depends_on:** none  
**Gaps:** G-AUTH-02

## Objetivo
Tras `POST /member/init`, devolver `token` Sanctum usable en rutas `auth:member_api`.

## Contexto obligatorio
- `app/Http/Controllers/Api/AnonymousMemberController.php`
- `app/Models/Member.php`
- `docs/mobile/fixtures/member-init.json`

## Pasos
1. Tras crear/recuperar member, `createToken(...)->plainTextToken`.
2. Incluir `token` en JSON 200.
3. Actualizar fixture y OpenAPI attributes si existen.
4. Regenerar docs swagger si el proyecto lo soporta (`php artisan l5-swagger:generate`).

## Definition of Done
Cliente puede `Authorization: Bearer` + `GET /member/` inmediatamente después de init.

## No hacer
Cambiar modelo de device UUID.

