# TASK-003 — GET /member/identity

**Platform:** backend  
**Phase:** F1  
**depends_on:** TASK-002  
**Gaps:** G-MEM-08

## Objetivo
Endpoint autenticado que expone payload para QR Member.

## Pasos
1. Ruta `GET member/identity` en grupo auth member_api.
2. Response: unique_identifier, device_code, display_name, member id.
3. Fixture `fixtures/member-identity.json`.

## DoD
Staff lookup funciona con el identifier expuesto.

