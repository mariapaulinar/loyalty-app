# 09 — Especificación UI para Figma/no-code

## Propósito y uso

Esta especificación es agnóstica de la herramienta: puede ser usada por Figma, Figma Make, FlutterFlow, Draftbit, un equipo iOS/Android o Codex. Todos deben producir la misma experiencia funcional tomando la PWA como referencia, aunque utilicen componentes nativos diferentes.

No completar ambigüedades inventando comportamiento. Ante una omisión, consultar 05, 06, 10, el código PWA y el contrato API, en ese orden. Las mejoras no comprobadas deben aparecer en una sección separada y rotulada “Propuesta — requiere aprobación”.

## Archivo

Páginas: 00 Foundations; 01 Components; 02 Authentication; 03 Discover; 04 Wallet; 05 Loyalty; 06 Stamp Cards; 07 Vouchers; 08 Account; 09 Secondary; 10 States; 11 Prototype Journeys; 12 Traceability. Nombrar frames con IDs de 05.

La página 12 debe contener la matriz frame → fuente PWA → acción/estado → endpoint/gap → criterio de aceptación.

## Contrato obligatorio de cada frame

Cada frame y variante debe incluir en su descripción:

- Screen ID y nombre.
- Objetivo del miembro.
- Fuente PWA: ruta, Blade/componente y controlador.
- Contexto de entrada y salida.
- Datos requeridos y reglas de visibilidad.
- Jerarquía exacta del contenido.
- Acción principal y acciones secundarias.
- Estados: loading, empty, error, offline y los específicos del dominio.
- Comportamiento de back y continuidad post-auth.
- Adaptación nativa aplicada y por qué no altera el resultado.
- Endpoint real o API GAP.
- Criterios observables de aceptación.

No marcar un frame como Ready for Build si falta alguno de estos elementos.

## Decisiones UX obligatorias para V1 — frontend

Estas decisiones reducen fricción sin modificar reglas ni contratos del backend:

1. Ocultar bottom navigation durante autenticación, OTP, recuperación, confirmaciones y QR.
2. Hacer tocable toda la superficie de cada tarjeta en Descubrir y Billetera.
3. Mostrar una acción visible de uso rápido cuando el contexto sea inequívoco.
4. Añadir “Usados recientemente” en Billetera con persistencia local; no altera pertenencia ni saldos.
5. Incluir “Abrir mi billetera” como acceso prominente desde Descubrir.
6. Usar oro Lealmi para el CTA principal; verde/morado/colores de partner identifican producto o estado, no cambian la posición del CTA.
7. Mostrar logo, nombre e identidad del socio dentro de su producto; el chrome global sigue siendo Lealmi.
8. Distinguir por texto, icono y explicación “Recibir sello” de “Retirar premio”; no depender solo del color.
9. No mostrar canje completado por anticipación. Tras cerrar/volver del QR, refrescar el recurso y mostrar “Esperando confirmación” hasta observar el cambio.
10. Permitir que un deep link/QR físico del socio abra su producto; se usa la cámara del teléfono y no se añade escáner Member.
11. Presentar una ayuda contextual de una sola vez: “Guarda tus beneficios en la Billetera y muestra el QR al personal cuando visites el negocio”.
12. Mantener textos secundarios legibles: cuerpo 14–16 sp, soporte 12–14 sp, CTA 14–16 sp y navegación mínimo 12 sp.

## Sistema y componentes

Usar los tokens y branding de 02; fallback Lealmi #FCD34D. Construir componentes con variantes y propiedades, no frames aislados. Conservar el mismo vocabulario y significado entre plataformas. Las diferencias iOS/Android se limitan a patrones del sistema: navegación, selección, diálogos, teclado, safe areas, permisos y feedback.

La interfaz debe evitar espacios vacíos accidentales y mostrar contenido realista del socio. Se priorizan cifras, progreso, vigencia, logo y acción inmediata sobre decoración. El CTA principal conserva ubicación y tratamiento visual predecibles entre puntos, sellos y cupones.

## Descubrir

Hero de marca con CTA “Abrir mi billetera”; luego programas, cupones y sellos. Cards completamente tocables. Ordenar vencimiento próximo primero. Empty con CTA contextual. Conservar filtros de actividad, visibilidad y elegibilidad del código web.

## Billetera

Saludo por hora local; acceso local a “Usados recientemente”; métricas condicionales de puntos, programas, cupones usados y sellos. Orden canónico: sellos, loyalty, nivel, cupones. Sin espacios para secciones vacías. Vacío → Explorar programas. No cambiar los criterios que determinan si una tarjeta, inscripción o cupón pertenece a la billetera.

Cada tarjeta identifica claramente al socio y facilita llegar al detalle/QR. El acceso rápido nunca omite validaciones del detalle.

## Loyalty

Back, tarjeta premium con identidad del socio, CTA dorado Mostrar QR, contacto, tabs Rewards/History/Rules, nivel y añadir/quitar/compartir. QR blanco 220–280 px con programa, identificador y “Muéstralo al personal”. El QR no muestra bottom navigation.

Reward: galería, descripción, costo, saldo y estados disponible/insuficiente/vencido/login. Claim: saldo antes/después y QR firmado. El cliente no calcula elegibilidad ni construye el payload del QR.

Después de mostrar el QR: esperando confirmación → refresco → completado confirmado o QR aún vigente. No inferir éxito solo porque se cerró el sheet.

## Sellos

Tarjeta, progreso, meta y premios pendientes; tabs Progress/History/Rules. CTA dorado “Mostrar QR para recibir sello”. Premio pendiente usa CTA dorado independiente “Retirar premio” y abre su propio QR. Salir conserva progreso. No unificar ambos QR ni convertir al miembro en escáner.

## Cupón

Tarjeta con identidad del socio, valor, código, vigencia, CTA dorado “Usar cupón” y acción secundaria “Copiar código”; tabs Details/History/Rules. Campos condicionales: mínimo, límites, producto gratis, bonus y restricciones. Estados válido, por vencer, vencido, agotado, guardado y reclamado.

Campaña: oferta, descuento, vencimiento, disponibilidad y Reclamar. Éxito con celebración, código, Ver cupón y Ver en Billetera; variantes ya reclamado/agotado/pausado.

## Cuenta y secundarias

Perfil, localización y privacidad; cambios sensibles con OTP. Enter code con cuatro casillas. Referidos con enlace/estadísticas. Solicitud/envío de puntos con tarjeta, saldo, cantidad y confirmación.

## Prototipo

Conectar al menos los journeys de 10 con variantes felices, autenticación interrumpida y errores relevantes. Cada acción debe terminar en el mismo estado funcional que la PWA. Los flujos no soportados por API pueden prototiparse, pero deben llevar la etiqueta API GAP — no implementable aún.

## Accesibilidad

WCAG AA, Dynamic Type, labels, no depender del color, targets 44 px y reducción de movimiento.
