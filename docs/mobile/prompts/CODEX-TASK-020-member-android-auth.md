# Prompt Cursor/Agent — TASK-020 (Member Android auth)

Workspace: `loyalty-member-android/` + `loyalty-app/docs/mobile/`.

`API_BASE_URL` = host **sin** `/api/` (ej. `https://lealmi.com`).

---

## Prompt (copiar)

```text
Eres un agente de implementación para Loyalty Member (Android).

## Alcance
- App: Loyalty Member ONLY · repo loyalty-member-android/ · com.lealmi.loyalty.member
- TASK: docs/mobile/tasks/TASK-020-android-auth.md
- NO Staff, Role Gate, Partner/Admin
- NO TabBar con tab QR; nav = Home + My Cards (+ Profile). QR de caja es TASK-030 en detalle de card
- NO inventes endpoints

## Lectura obligatoria
1. docs/mobile/AGENTS.md
2. docs/mobile/05-screen-map.md (nav + QR contrato)
3. docs/mobile/tasks/TASK-020-android-auth.md
4. docs/mobile/04-auth-and-sessions.md
5. docs/mobile/03-api-contract.md
6. docs/mobile/02-design-system.md
7. screens/member/M_Splash.md, M_Login.md, M_Register.md, M_SwitchAccount.md, M_Profile.md
8. flows/member-anonymous-init.md
9. Código existente loyalty-member-android/

Paridad: resources/views/member/auth + layout chrome (top header), Compose nativo.

## Config
API_BASE_URL = {{API_BASE_URL}}
DEFAULT_LOCALE = {{DEFAULT_LOCALE}}
Base: {API_BASE_URL}/api/{locale}/v1

## DoD TASK-020
1. ThemeStore paint-first + GET /mobile/branding (ETag); no bloquear Splash
2. device_uuid + timezone IANA del device
3. Splash → init issue_token:true → Home placeholder OR Login si requires_login
4. Login / Register / Switch (issue_token en switch)
5. GET /member/identity cache para Profile/DeviceCode (NO tab QR)
6. Logout: solo UX registered; borrar token Member; conservar device_uuid
7. 401 limpia solo member token
8. Storage seguro; nunca loguear tokens

## Entrega
Código en loyalty-member-android/; resumen de prueba staging; sin commit salvo pedido.
```

Tras TASK-020 → `TASK-030` (Home discovery, My Cards, detalle + QR contextual URL staff).
