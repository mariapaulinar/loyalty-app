# TASK-008 — Mobile branding API

**App:** backend  
**Platform:** backend  
**Phase:** F1  
**depends_on:** (none)  
**Status:** DONE

## Objetivo

Endpoint público para white-label de apps nativas, alineado a `<x-ui.brand-styles />` / `ColorHelper`, sin cambiar el PWA.

## Entregado

- `GET /api/{locale}/v1/mobile/branding`
- `MobileBrandingService` + cache 1h + `forgetCache` en settings/logos
- ETag / 304 / `Cache-Control`
- Fixture `docs/mobile/fixtures/mobile-branding.json`
- Docs: `02-design-system.md`, `03-api-contract.md`, gap `G-BRAND-01`

## DoD

- [x] Respuesta incluye `primary` 50–950, logos, `app_name`, PWA colors, `version`
- [x] PWA Blade sin cambios de comportamiento
- [x] Specs + fixture actualizados
