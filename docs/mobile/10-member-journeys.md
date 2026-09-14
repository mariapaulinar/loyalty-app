# 10 — Journeys Member

## Regla de coherencia

Los journeys describen resultados funcionales compartidos por PWA, iOS y Android. La presentación y transición pueden adaptarse a cada plataforma; los pasos de negocio, las validaciones, la continuidad, los bloqueos y el estado final deben conservarse.

Cualquier herramienta debe implementar también variantes de autenticación interrumpida, error y reintento. No basta con prototipar únicamente el happy path.

## Principal V1

| Etapa | Acción | Sistema | Resultado |
|---|---|---|---|
| Entrada | App/deep link | Branding + sesión | Descubrir/Login sin navegación inferior |
| Descubrir | Explorar o Abrir mi billetera | Filtra activos | Selección/Billetera |
| Evaluar | Ver reglas e identidad del socio | Estado/elegibilidad | Decisión |
| Alta | Seguir/guardar/inscribir | Auth si aplica | Billetera |
| Acceso rápido | Tocar reciente o tarjeta completa | Abre mismo detalle contextual | Producto listo para usar |
| Usar | Tocar CTA principal dorado | Refresca datos | QR contextual sin navegación |
| Acumular | Mostrar QR | Staff registra | Esperando confirmación |
| Beneficio | Elegir reward/cupón | Valida | QR canje |
| Confirmar | Volver/foreground/refrescar | Consulta estado actual | Éxito solo si backend confirma |
| Retener | Volver a Billetera | Guarda reciente local y muestra progreso | Reutilización |
| Control | Cuenta | OTP sensible | Datos actualizados |

## Primer uso

Login/registro → mensaje contextual único → Descubrir → Abrir mi billetera o elegir producto → detalle.

Mensaje: “Guarda tus beneficios en la Billetera y muestra el QR al personal cuando visites el negocio”. No crear onboarding multipágina.

## Auth pendiente

Detalle → acción → login/check → password/OTP → token → ejecutar → destino original. Email inexistente → registro → OTP → token → ejecutar.

Criterio de paridad: autenticarse nunca debe hacer que el miembro pierda la intención, el recurso ni los parámetros del deep link original. Auth no muestra navegación inferior.

## Acceso guiado por el socio

QR o enlace del socio → cámara del teléfono → deep link del producto → auth si aplica → mismo detalle → CTA contextual.

Guion único para el socio: “Abre Lealmi, entra en Billetera y toca nuestra tarjeta. Allí verás el botón para usarla”. Si existe QR físico: “Escanea este código con la cámara para abrir nuestra tarjeta”.

## Puntos

Billetera/reciente/deep link → programa → CTA Mostrar QR para sumar puntos → staff escanea → cerrar/foreground → refrescar → saldo actualizado o esperando confirmación.

Reward → costo/saldo → confirmar → QR firmado → staff → refrescar → saldo nuevo confirmado.

Criterio de paridad: saldo, elegibilidad y costo provienen del backend; después del canje se invalida o refresca el estado anterior.

## Sellos

Billetera/reciente/deep link → programa → CTA Mostrar QR para recibir sello → staff → refrescar → progreso actualizado.

Meta completada → CTA Retirar premio → QR de premio → staff → refrescar → contador/premio actualizado.

Criterio de paridad: recibir sello y retirar premio mantienen textos, iconos, explicaciones y QR independientes.

## Cupones

Descubrir/deep link → guardar o reclamar → Billetera/reciente → cupón → CTA Usar cupón → QR/código → staff valida → refrescar → uso registrado.

Criterio de paridad: ya reclamado, agotado, vencido, pausado y no elegible no se convierten en errores genéricos; conservan explicación y salida contextual.

## Excepciones

401 conserva destino; offline bloquea mutaciones; vencido/agotado explica y bloquea; insuficiente muestra faltante; duplicado refleja estado actual; desactivado ofrece retorno a Descubrir; confirmación no observada mantiene “Esperando confirmación” y ofrece actualizar.

## Validación multiplataforma

Para aprobar cada journey, ejecutarlo en la PWA y en la implementación candidata con los mismos datos y comprobar:

1. mismo punto de entrada e intención;
2. misma información y restricciones antes de actuar;
3. misma mutación o bloqueo;
4. mismo estado final de saldo, progreso, cupón o cuenta;
5. retorno y back previsibles;
6. mensajes críticos equivalentes;
7. ausencia de funciones inventadas o pérdidas de contexto;
8. máximo tres decisiones del miembro desde Billetera hasta mostrar el QR;
9. ningún falso éxito antes de confirmación del backend.
