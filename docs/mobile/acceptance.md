# Aceptación — Member App

## Condición de producto

- La PWA Member de producción es la referencia funcional y conductual.
- La implementación preserva modelo mental, contenido, jerarquía, reglas, acciones, estados, mensajes críticos, continuidad y resultado.
- Las diferencias de UI corresponden a patrones nativos y no alteran significado, restricciones ni resultado.
- Toda capacidad no comprobada en la PWA está marcada como “Propuesta — requiere aprobación”.
- Cada frame tiene trazabilidad a vista/ruta/controlador PWA, endpoint o gap y criterio observable.
- La validación usa los mismos casos y datos en PWA y app candidata.

## Facilidad de uso V1 — frontend

- Bottom navigation oculta en auth, OTP, recuperación, confirmaciones y QR.
- Toda tarjeta de Descubrir/Billetera es tocable y tiene estado pressed/focus.
- Existe acceso visible Abrir mi billetera desde Descubrir.
- Billetera puede mostrar Usados recientemente usando almacenamiento local.
- Desde Billetera hasta mostrar un QR no hay más de tres decisiones del miembro.
- El CTA principal usa oro Lealmi y mantiene posición/patrón consistente.
- Logo y nombre del socio son reconocibles en su producto.
- Recibir sello y Retirar premio se distinguen por texto, icono, explicación y QR.
- El primer uso explica una sola vez que el miembro muestra su QR al personal.
- El socio puede usar un deep link/QR físico para abrir su producto sin escáner dentro de la app.
- Texto normal mínimo 14 sp; soporte 12 sp; CTA 14 sp; navegación 12 sp.
- No se muestra éxito hasta observar confirmación del backend.
- Al volver del QR, la app refresca el recurso; mientras tanto muestra Esperando confirmación.
- Estas mejoras usan navegación, caché/almacenamiento local y endpoints existentes; no requieren modificar el core del backend.

## Cobertura

- Solo Member; Descubrir/Billetera/Cuenta separados; sin QR tab ni pantallas de otros roles.
- Login check, password/OTP, registro OTP, cooldown, token cifrado, 401 y pending action completos.
- Todos los IDs de 05 tienen frame y estados.
- Orden de 09 respetado.
- Loading, empty, error, expired, exhausted, insufficient, duplicate, disabled, waiting-confirmation y offline diseñados.
- Deep links conservan recurso y parámetros; el retorno post-auth completa la acción original.
- QR contextual desde detalle usa URL/payload oficial del backend; el miembro muestra y el staff escanea.
- Puntos, sellos, rewards y cupones conservan reglas de elegibilidad, vigencia, saldo, progreso y canje de la PWA.
- Billetera conserva criterios de pertenencia, orden de secciones y ocultamiento de vacíos.
- Branding paint-first; fallback #FCD34D/#000000/#FFFFFF; partner solo en su producto; claro/oscuro; 44 px y AA.
- No llamar endpoints inexistentes. Cada gap se implementa o la función queda explícitamente deshabilitada.
- Figma usa IDs estables, página de trazabilidad y conecta journeys felices, interrumpidos y de error de 10.

## Definition of Done por pantalla

Una pantalla está lista para construir solo si contiene objetivo, fuente PWA, datos, reglas de visibilidad, jerarquía, acciones, estados, navegación, adaptación nativa, dependencia API/gap y criterios de aceptación.

Una pantalla está aceptada solo si con los mismos datos que la PWA:

1. muestra información y prioridades equivalentes;
2. permite o bloquea las mismas acciones;
3. comunica las mismas condiciones críticas;
4. conserva el contexto al navegar o autenticarse;
5. produce el mismo estado de negocio;
6. no introduce funciones ni reglas no aprobadas;
7. permite al socio explicarla con el guion definido en 10;
8. evita pasos innecesarios y falsos estados de éxito.

La semejanza visual por sí sola no demuestra paridad.
