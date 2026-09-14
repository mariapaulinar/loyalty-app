# Flow: Member anonymous init

> **Lealmi:** no usar. Modo anónimo desactivado. Arranque nativo = splash → login si no hay token. Ver `05-screen-map.md`.

**ID:** `FLOW_M_ANON_INIT`  
**App:** Loyalty Member  
**Screens:** `M_Splash` → `M_Home`  
**Depends:** G-AUTH-02 (issue_token)

## Pasos

1. Splash: tema paint-first + timezone device.
2. Paralelo: `GET /mobile/branding` + `device_uuid`.
3. `POST /member/init` `{ device_uuid, issue_token: true }`.
4. Persistir member + token (+ identity fetch).
5. Navegar a **`M_Home` discovery** (no wallet-only).

**PWA:** init sin `issue_token`. Nativo siempre con token opt-in.

## Errores

- `400 requires_login` → `M_Login`
- Red → retry; no member fake local

## DoD

Anónimo ve Home discovery sin login cuando anonymous enabled.
