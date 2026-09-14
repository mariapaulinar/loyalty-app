# Lealmi Member App — documentación canónica

Esta carpeta especifica la aplicación que usan los miembros de Lealmi. Se construyó interpretando directamente las rutas, controladores, vistas Blade, componentes y reglas de negocio de la rama main.

La documentación móvil anterior no define el producto. En caso de contradicción, la prioridad es:

1. Código web de miembros en resources/views/member y rutas/controladores activos.
2. Esta documentación canónica.
3. Contrato REST realmente registrado en routes/api.php.
4. Documentos auxiliares, fixtures y tareas históricas.

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
| 06-pwa-component-parity.md | Correspondencia web → UI nativa |
| 07-navigation-shell.md | Navegación y deep links |
| 08-implementation-status.md | Cobertura API por experiencia |
| 09-member-ui-spec.md | Especificación para Figma/no-code |
| 10-member-journeys.md | Journeys y transiciones |
| backend-gaps.md | Capacidades faltantes |
| acceptance.md | Criterios de aceptación |

Una herramienta de diseño debe leer 00, 02, 05, 09 y 10. Un generador de apps debe leer todos los documentos canónicos, contrastar routes/api.php y no inventar endpoints.

La paleta oficial se obtiene de GET /api/{locale}/v1/mobile/branding. El fallback Lealmi es oro #FCD34D sobre #FFFFFF.
