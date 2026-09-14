# 04 — Autenticación y sesiones

## Entrada

Email → login/check. Si no existe, registro precargado. Si has_password=true, contraseña y alternativa OTP; si false, solo OTP. Verificación exitosa guarda token y retoma pending_action o abre Descubrir.

## Registro

Nombre, email, timezone, consentimiento obligatorio y marketing opcional. register/otp/start → OTP seis dígitos → resend con cooldown → register/otp/verify → token.

OTP usa autoavance/autoenvío, email enmascarado, intentos restantes si llegan y cooldown. Diferenciar login de verify_email.

## Recuperación

Solo ofrecer si has_password=true. Enlace firmado por email abre deep link para nueva contraseña.

## Acción pendiente

Guardar tipo, identificadores y URL para follow_card, enroll_stamp_card, save_voucher, claim_voucher_batch, send_points u open_reward. Borrarla solo al completar o cancelar.

## Sesión

En 401 limpiar token, conservar deep link no sensible y abrir Login sin loops. Logout revoca token con red y limpia datos sensibles.

## Anónimo

El backend lo soporta por feature flag. La app estándar lo trata desactivado hasta decisión de producto. Si se activa, init/switch solicitan issue_token=true.
