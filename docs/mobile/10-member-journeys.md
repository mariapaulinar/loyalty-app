# 10 — Journeys Member

## Regla de coherencia

Los journeys describen resultados funcionales compartidos por PWA, iOS y Android. La presentación y transición pueden adaptarse a cada plataforma; los pasos de negocio, las validaciones, la continuidad, los bloqueos y el estado final deben conservarse.

Cualquier herramienta debe implementar también variantes de autenticación interrumpida, error y reintento. No basta con prototipar únicamente el happy path.

## Principal

| Etapa | Acción | Sistema | Resultado |
|---|---|---|---|
| Entrada | App/deep link | Branding + sesión | Descubrir/Login |
| Descubrir | Explorar | Filtra activos | Selección |
| Evaluar | Ver reglas | Estado/elegibilidad | Decisión |
| Alta | Seguir/guardar/inscribir | Auth si aplica | Billetera |
| Usar | Abrir elemento | Refresca datos | QR |
| Acumular | Mostrar QR | Staff registra | Progreso |
| Beneficio | Elegir reward/cupón | Valida | QR canje |
| Canjear | Mostrar QR/código | Staff confirma | Éxito |
| Retener | Volver | Progreso/vencimientos | Reutilización |
| Control | Cuenta | OTP sensible | Datos actualizados |

## Auth pendiente

Detalle → acción → login/check → password/OTP → token → ejecutar → destino original. Email inexistente → registro → OTP → token → ejecutar.

Criterio de paridad: autenticarse nunca debe hacer que el miembro pierda la intención, el recurso ni los parámetros del deep link original.

## Puntos

Programa → seguir → QR → puntos → rewards → saldo → claim → QR firmado → staff → saldo nuevo.

Criterio de paridad: saldo, elegibilidad y costo provienen del backend; después del canje se invalida o refresca el estado anterior.

## Sellos

Inscribir → QR → sello → repetir → premio pendiente → QR premio → staff → contador nuevo.

Criterio de paridad: el QR para recibir sello y el QR para retirar premio mantienen contextos independientes.

## Cupones

Descubrir/deep link → guardar o reclamar → Billetera → QR/código → staff valida → uso registrado.

Criterio de paridad: ya reclamado, agotado, vencido, pausado y no elegible no se convierten en errores genéricos; conservan explicación y salida contextual.

## Excepciones

401 conserva destino; offline bloquea mutaciones; vencido/agotado explica y bloquea; insuficiente muestra faltante; duplicado refleja estado actual; desactivado ofrece retorno a Descubrir.

## Validación multiplataforma

Para aprobar cada journey, ejecutarlo en la PWA y en la implementación candidata con los mismos datos y comprobar:

1. mismo punto de entrada e intención;
2. misma información y restricciones antes de actuar;
3. misma mutación o bloqueo;
4. mismo estado final de saldo, progreso, cupón o cuenta;
5. retorno y back previsibles;
6. mensajes críticos equivalentes;
7. ausencia de funciones inventadas o pérdidas de contexto.
