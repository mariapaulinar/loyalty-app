# Lealmi Member App — documentación canónica

Esta carpeta especifica la aplicación que usan los miembros de Lealmi. Se construyó interpretando directamente las rutas, controladores, vistas Blade, componentes y reglas de negocio de la rama main.

## Principio rector: continuidad con la PWA

La PWA de producción es la referencia funcional y de experiencia para la app Member. Toda implementación en Figma, no-code, iOS, Android o generada por Codex debe preservar el modelo mental que ya conocen los miembros: contenido, jerarquía, reglas, acciones, estados, mensajes y resultado de cada journey.

La especificación es agnóstica de la herramienta, no agnóstica del producto. Una herramienta puede traducir controles web a patrones nativos, pero no reinterpretar libremente el comportamiento de Lealmi.

Cada decisión debe clasificarse así:

1. **Paridad obligatoria:** comportamiento, reglas, contenido, estados y resultados comprobados en la PWA.
2. **Adaptación nativa permitida:** controles, gestos, navegación, sheets, diálogos, áreas seguras y convenciones de iOS/Android que no cambien el significado ni el resultado.
3. **Mejora propuesta:** capacidad o cambio no presente en la PWA. Debe quedar rotulado como propuesta y requiere aprobación de producto antes de implementarse.

En caso de contradicción, la prioridad es:

1. Código web de miembros en resources/views/member y rutas/controladores activos.
2. Esta documentación canónica.
3. Contrato REST realmente registrado en routes/api.php.
4. Documentos auxiliares, fixtures y tareas históricas.

La documentación móvil anterior no define el producto. Tampoco se debe asumir que una capacidad documentada existe en el API: debe verificarse en las rutas y controladores activos.

## Alcance

Incluye únicamente la experiencia Member: Descubrir, registro/login, My Cards, puntos, recompensas, niveles, sellos, cupones, Cuenta, privacidad, localización, deep links, funciones secundarias y estados transversales.

No incluye Admin, Partner, Staff, POS, billing, Shopify ni Agent API.

## Documentos

| Documento | Uso |
|---|---|
| 00-product-context.md | Producto, actores y alcance |
| 01-architecture.md | Arquitectura funcional |
| 02-design-system.md | Paleta Lealmi, tokens y componentes |
| 03-api-contract.md | API disponible y límites |
| 04-auth-and-sessions.md | Registro, login, OTP y sesión |
| 05-screen-map.md | Inventario completo de pantallas |
| 06-pwa-component-parity.md | Correspondencia normativa PWA → UI nativa |
| 07-navigation-shell.md | Navegación y deep links |
| 08-implementation-status.md | Cobertura API por experiencia |
| 09-member-ui-spec.md | Especificación para Figma/no-code |
| 10-member-journeys.md | Journeys y transiciones |
| 11-api-gaps-cursor-spec.md | Contratos implementables por Cursor y Laravel Cloud |
| backend-gaps.md | Índice resumido de capacidades faltantes |
| acceptance.md | Criterios de aceptación |

Una herramienta de diseño debe leer 00, 02, 05, 06, 09, 10 y acceptance. Un generador de apps debe leer todos los documentos canónicos, contrastar routes/api.php y no inventar endpoints. Un agente backend debe usar 11 como instrucción de implementación y backend-gaps.md solo como índice.

Antes de aprobar cualquier implementación se debe validar cada frame contra la matriz y la plantilla de 06 y 09. Una diferencia visual puede ser aceptable como adaptación nativa; una diferencia de reglas, estados, contenido, continuidad o resultado no lo es.

La paleta oficial se obtiene de GET /api/{locale}/v1/mobile/branding. El fallback Lealmi es oro #FCD34D sobre #FFFFFF.
