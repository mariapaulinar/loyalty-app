# M_SwitchAccount

**PWA:** `member/account/switch-account.blade.php`

## Lealmi

**No aplica.** El modo anónimo está desactivado en producción; esta pestaña solo existe en el PWA para miembros anónimos (`ProfileDataDefinition`). La app nativa Lealmi no implementa switch por código.

Multi-dispositivo: el usuario inicia sesión con **email + OTP** (`M_Login`).

## API (referencia plataforma)

`POST /member/session/switch`, `account/login-email/*` — no usados en Lealmi V1.
