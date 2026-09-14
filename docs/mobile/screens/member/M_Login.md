# M_Login

**PWA:** `member/auth/login.blade.php`  
**Ruta web:** `/login`

## Flujo PWA (obligatorio)

```
Paso 1 — Email + Continuar
  POST login/check
    ├─ no existe → redirect registro con email
    └─ existe → Paso 2 (method)

Paso 2a — has_password=true
  - Bienvenido de nuevo + email + Cambiar correo
  - Contraseña + ¿Olvidaste tu contraseña?
  - Iniciar sesión
  - divisor «o»
  - Enviarme un código de inicio de sesión

Paso 2b — has_password=false (passwordless)
  - Misma cabecera
  - Caja info: «Te enviaremos un código de 6 dígitos por correo.»
  - Botón primario: Enviarme un código de inicio de sesión

Paso 3 — OTP verify (otp-verify.blade.php)
  - PIN 6 dígitos, reenviar, probar otro método
```

**Lealmi:** sin modo anónimo ni switch en perfil; multi-dispositivo vía login email + OTP en esta pantalla.

## API nativa (paridad)

| Paso | Endpoint |
|------|----------|
| check | `POST /member/login/check` |
| send OTP | `POST /member/login/otp/send` |
| verify OTP | `POST /member/login/otp/verify` → `{ token }` |
| password | `POST /member/login` |

## Copy

Español `lang/es_ES/otp.php`. App forzada a `es-ES`.

## DoD

- [ ] Paso 1 solo email; check API antes de paso 2
- [ ] Paso 2b OTP-only si `!has_password` (como captura PWA)
- [ ] Paso 2a password + alternativa OTP si `has_password`
- [ ] Paso 3 verify OTP
- [ ] Email inexistente → registro con email prefilled
- [ ] Sin Switch en login
