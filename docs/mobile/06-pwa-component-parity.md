# 06 — Paridad PWA

## Regla normativa

La PWA Member de producción es la referencia funcional y conductual. Paridad significa conservar información, jerarquía, reglas, acciones, estados, mensajes, continuidad y resultado; no copiar HTML ni reproducir limitaciones accidentales del navegador.

Una implementación no puede eliminar, renombrar conceptualmente, reordenar de forma que cambie la prioridad, ni alterar una regla comprobada sin registrar la decisión como mejora propuesta y obtener aprobación de producto.

## Fuentes comprobables

| Dominio | Fuente |
|---|---|
| Shell | member/layouts/default.blade.php |
| Descubrir | member/home*.blade.php |
| Billetera | member/my-cards.blade.php + PageController@dashboard |
| Puntos/rewards | member/card/* |
| Sellos | member/stamp-card/index.blade.php |
| Cupones | member/voucher/index + member/vouchers/claim |
| Auth | member/auth/* |
| Cuenta | account/privacy-data, switch-account y definición account |
| Secundarias | member/code, referrals, point_request |
| Offline | pwa/offline.blade.php |

## Clasificación obligatoria de decisiones

| Clase | Qué comprende | Tratamiento |
|---|---|---|
| Paridad obligatoria | Datos, reglas, permisos, elegibilidad, acciones, estados, textos críticos, orden funcional y resultado | Debe coincidir con la PWA |
| Adaptación nativa permitida | Bottom navigation, back nativo, sheets, diálogos, controles, gestos, safe areas, feedback háptico | Puede variar sin alterar significado ni resultado |
| Mejora propuesta | Funciones nuevas, cambios de journey, nueva jerarquía, automatizaciones o comportamiento no comprobado | Rotular, justificar y aprobar antes de construir |
| Deuda excluida | Bugs, duplicados, menús de otros roles, código huérfano o acciones comentadas | No trasladar a la app |

## Matriz mínima de paridad

| Área | Se debe conservar | Puede adaptarse |
|---|---|---|
| Autenticación | Método aplicable, OTP/password, acción pendiente y destino posterior | Teclado, autofill, biometría futura y presentación del formulario |
| Descubrir | Filtros de actividad/visibilidad, tipos de producto, prioridad y empty state | Layout del catálogo y patrones de scroll |
| Billetera | Criterios de pertenencia, orden sellos → puntos → niveles → cupones y ocultamiento de secciones vacías | Cards, carruseles o listas si preservan jerarquía |
| Puntos | Saldo, nivel, reglas, rewards, elegibilidad y QR contextual | Tabs, sheets y animaciones |
| Sellos | Progreso, meta, premio pendiente, inscripción/salida y QR independientes | Representación visual del progreso |
| Cupones | Estado, vigencia, límites, guardado/reclamo, código y restricciones | Composición de la tarjeta y presentación del código |
| QR | Contexto, payload oficial, superficie blanca, identificador e instrucción al miembro | Sheet o pantalla completa |
| Errores/offline | Qué se permite, qué se bloquea, mensaje y recuperación | Patrón visual de alerta o retry |
| Deep links | Recurso, autenticación pendiente y retorno al destino original | Transición y animación de entrada |

## Reglas funcionales transversales

- Descubrir muestra únicamente loyalty, vouchers y stamps activos y visibles conforme a las reglas web.
- Billetera conserva el orden sellos → puntos → niveles → cupones y no reserva espacio para secciones vacías.
- Los detalles conservan información, acciones y agrupaciones equivalentes a sus tabs.
- El miembro muestra QR; no se debe inventar un escáner Member para acumular o canjear.
- Cada QR es contextual y debe provenir del backend, no reconstruirse con supuestos en el cliente.
- Contactar, compartir y añadir/quitar son acciones secundarias.
- Elegibilidad, vigencia, disponibilidad y saldo se muestran antes de confirmar un canje.
- Si la autenticación interrumpe una acción, la app la conserva y regresa al destino y resultado esperados.
- Un estado expirado, agotado, insuficiente, duplicado, desactivado u offline debe bloquear exactamente las mutaciones que bloquea la PWA y ofrecer una salida clara.

## Trazabilidad obligatoria por pantalla

Cada pantalla o estado debe documentar:

1. ID estable de 05.
2. Objetivo del miembro.
3. Ruta, vista y controlador PWA de referencia.
4. Datos y condiciones de visibilidad.
5. Jerarquía y contenido.
6. Acciones principales y secundarias.
7. Estados, validaciones y errores.
8. Entrada, salida y comportamiento de back.
9. Adaptaciones nativas permitidas.
10. Dependencia API y gap, si existe.
11. Criterios observables de aceptación.

Sin esta información el frame se considera exploratorio, no listo para construcción.

## Validación

La revisión debe comparar PWA y app con el mismo caso de prueba y los mismos datos. Se acepta una presentación nativa diferente cuando el miembro encuentra la misma información, toma una acción equivalente, recibe las mismas restricciones y alcanza el mismo resultado.

No replicar menús de otros roles, Agent Keys, enlaces para negocios, acciones comentadas como navegación primaria ni bugs/botones duplicados.
