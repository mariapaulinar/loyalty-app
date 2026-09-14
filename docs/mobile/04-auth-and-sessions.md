# 04 — Auth and sessions

Hay **dos apps**. Cada una solo maneja su propio token.

## Lealmi — modelo de autenticación Member

**Importante:** no hay un único ajuste “OTP sí/no”. El comportamiento sale de **tres capas** distintas:

| Capa | Lealmi | Efecto |
|------|--------|--------|
| Modo anónimo | **Desactivado** | Sin invitados ni `POST /member/init` en nativo. Login obligatorio. |
| Registro | **OTP passwordless** (web actual) | Cuenta nueva **sin contraseña** en BD (`password = null`). |
| Login | **Híbrido por cuenta** | `POST /member/login/check` → `has_password` según si el miembro tiene contraseña guardada. |

### Registro (siempre igual en Lealmi hoy)

1. Formulario: nombre, email, consentimiento, accepts_emails — **sin campo contraseña**.
2. `POST /register` (web) o `POST /member/register/otp/start` (API nativa).
3. OTP 6 dígitos por email (`purpose: verify_email`).
4. Tras verificar → sesión (web) o token Sanctum (nativo).

### Login (depende de cada usuario)

Tras `POST /member/login/check`:

| `has_password` | Pantalla PWA / nativo |
|----------------|------------------------|
| `false` | Solo OTP: “Enviarme un código de inicio de sesión” → verificar 6 dígitos |
| `true` | Contraseña + enlace “¿Olvidaste…?” + alternativa “o envíame un código” (OTP) |

`has_password` = `members.password` no vacío (`OtpService::checkUser`).

**Usuarios nuevos** registrados con el flujo OTP actual → `has_password: false` → login **solo por código**.

**Usuarios legacy** (registro antiguo con contraseña por email) o quien **estableció contraseña en perfil** → `has_password: true` → login con contraseña u OTP.

### Forgot / reset password

Solo miembros con contraseña (`has_password: true`).

| Paso | Web | API nativa |
|------|-----|------------|
| Solicitar enlace | `POST member/password` (forgot) | `POST /member/password/forgot` |
| Nueva contraseña | enlace firmado GET → form | deep link → `POST /member/password/reset` |

No confundir con OTP de login (`purpose: login`).

| Mecanismo | Uso |
|-----------|-----|
| OTP `login` | Iniciar sesión |
| OTP `verify_email` | Completar registro |
| OTP en perfil | Verificar identidad al guardar cambios sensibles (`editRequiresOtp`) |
| Forgot password | Enlace firmado por email → pantalla reset (no es OTP de login) |
| Login link firmado | Existe en `AuthService::sendLoginLink` (legacy/auxiliar) |

### Arranque nativo (Lealmi)

1. Splash: branding + timezone local.
2. ¿Token Sanctum válido? → `GET /member/identity` → Home.
3. Si no → **Login** (no llamar a `/member/init`).

### Logout

Solo para cuentas con **email** (miembro registrado). Tras logout → Login.

## Staff app

1. Login PWA: email → (club si multi) → password; OTP staff = P1.
2. API: `POST /staff/login` → `{ token, staff }`.
3. `401` → limpiar token → pantalla login.

## Storage (Member)

| Dato | Guardar |
|------|---------|
| API token Sanctum | sí |
| device_uuid | sí (switch legacy; opcional si no hay anónimo) |
| timezone IANA | sí |
| identity cache | sí (perfil, device_code) |
| locale | sí |

Android: EncryptedSharedPreferences. iOS: Keychain.

## Referencias código

- Login web: `member/auth/login.blade.php`, `Member\OtpController`
- Registro web: `Member\OtpController@postRegister` → `AuthService::registerWithOtp`
- API nativa: `MemberLoginOtpApiController`, `MemberRegisterOtpApiController`
- Check email: `OtpService::checkUser` → `has_password`
