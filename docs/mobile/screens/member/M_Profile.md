# M_Profile

**PWA:** `ProfileDataDefinition` — tabs para miembro **con sesión** (Lealmi: siempre registrado tras login)

## Tabs (nativo)

| Tab | Contenido |
|-----|-----------|
| Perfil | Nombre, email, código dispositivo, logout |
| Localización | Locale / timezone (lectura; edición P1) |
| Privacidad y datos | Placeholder GDPR (P1) |

## API

`GET /member/`, `POST /member/logout`

## DoD

Perfil usable; logout visible para cuentas con email.
