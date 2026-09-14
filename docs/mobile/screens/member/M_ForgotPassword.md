# M_ForgotPassword / M_ResetPassword

**PWA:** `member/auth/forgot-password.blade.php`, `member/auth/reset-password.blade.php`

## Flujo

1. **Forgot** (desde login paso contraseña): email → enlace por correo (válido 2 h).
2. **Reset**: usuario abre enlace del email → nueva contraseña → login.

Solo aplica a cuentas con contraseña (`has_password: true`). Cuentas OTP-only no usan este flujo.

## API

- `POST /member/password/forgot` — `{ email }`
- `POST /member/password/reset` — `{ email, password, expires, signature }` (misma firma que enlace web)

## Android

- `ForgotPasswordScreen` — desde «¿Olvidaste tu contraseña?»
- `ResetPasswordScreen` — deep link `*/reset-password?email&expires&signature`
- Intent filters: `loyalty-app.test`, `lealmi.com`

## DoD

Enlace enviado; reset con firma válida; vuelta a login con email prefilled.
