# Instrucciones para agentes — Lealmi Member App

## Objetivo

Construir o diseñar exclusivamente la app de miembros a partir del comportamiento real de la PWA. La documentación móvil histórica no sustituye el análisis del código.

## Lectura obligatoria

README.md → 00-product-context.md → 05-screen-map.md → 09-member-ui-spec.md → 10-member-journeys.md → 02-design-system.md → 03-api-contract.md → backend-gaps.md. Después abrir el Blade/controlador de la pantalla.

## Reglas

- Mantener separadas Descubrir y Billetera.
- Cuenta es destino propio en la propuesta nativa.
- No crear tab QR universal: el miembro muestra un QR contextual y el staff lo escanea.
- Conservar acciones pendientes después de login/registro.
- Usar branding runtime; fallback #FCD34D.
- Implementar loading, empty, error, expired, insufficient y success.
- No inventar endpoints; resolver o señalar gaps.
- No mezclar Admin, Partner, Staff, POS o Agent API.
- No asumir que tasks o screens históricos están actualizados.
- Mantener información, orden, reglas y estados del PWA; no copiar HTML literalmente.
