# M_Register

**PWA:** `member/auth/register.blade.php` + `register-verify.blade.php`

## Flujo (paridad PWA)

1. Formulario: nombre, email, consentimiento (requerido), accepts_emails — **sin contraseña**
2. OTP 6 dígitos (`verify_email`) → sesión con token Sanctum

## API

- `POST /member/register/otp/start`
- `POST /member/register/otp/resend`
- `POST /member/register/otp/verify` → `{ token }`

## Android

`RegisterScreen.kt` — pasos Form + OtpVerify.

## DoD

Cuenta creada + token almacenado + identity cacheada; copy español.
