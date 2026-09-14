# 03 — Contrato API Member

Base: {API_BASE_URL}/api/{locale}/v1. Headers protegidos: Accept application/json, Content-Type application/json y Authorization Bearer {token}. Locale español: es-es.

## Disponible

Branding público: GET /mobile/branding.

Auth: POST /member/login, /login/check, /login/otp/send, /login/otp/verify, /register, /register/otp/start, /register/otp/resend, /register/otp/verify, /password/forgot y /password/reset.

Sesión: GET /member, GET /member/identity, POST /member/logout y POST /member/account/login-email/send|verify.

Anónimo condicionado por configuración: /member/init, /member/session, /member/session/switch y /member/session/link-email.

Puntos: GET /member/all-cards, /followed-cards, /transacted-cards, /balance/{cardId}, /cards/{cardId}; POST|DELETE /cards/{cardId}/follow.

Sellos: GET /member/stamp-cards, /stamp-cards/{id}/history, /my-stamp-cards y /stamp-cards/{id}; POST|DELETE /stamp-cards/{id}/enroll.

Cupones: GET /member/my-vouchers y /vouchers/{id}; POST|DELETE /vouchers/{id}/save.

Códigos: POST /member/codes/redeem.

## Errores

400 regla de negocio; 401 limpiar token y autenticar; 403 permiso; 404 inexistente; 409 conflicto; 422 validación visible por campo.

Que una pantalla exista en PWA no implica API. Consultar backend-gaps.md y no usar Agent/Partner/Staff.
