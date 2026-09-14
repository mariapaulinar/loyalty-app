# Aceptación — Member App

## Condición de producto

- La PWA Member de producción es la referencia funcional y conductual.
- La implementación preserva modelo mental, contenido, jerarquía, reglas, acciones, estados, mensajes críticos, continuidad y resultado.
- Las diferencias de UI corresponden a patrones nativos y no alteran significado, restricciones ni resultado.
- Toda capacidad no comprobada en la PWA está marcada como “Propuesta — requiere aprobación”.
- Cada frame tiene trazabilidad a vista/ruta/controlador PWA, endpoint o gap y criterio observable.
- La validación usa los mismos casos y datos en PWA y app candidata.

## Cobertura

- Solo Member; Descubrir/Billetera/Cuenta separados; sin QR tab ni pantallas de otros roles.
- Login check, password/OTP, registro OTP, cooldown, token cifrado, 401 y pending action completos.
- Todos los IDs de 05 tienen frame y estados.
- Orden de 09 respetado.
- Loading, empty, error, expired, exhausted, insufficient, duplicate, disabled y offline diseñados.
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
6. no introduce funciones ni reglas no aprobadas.

La semejanza visual por sí sola no demuestra paridad.
