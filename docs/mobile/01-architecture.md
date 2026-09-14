# 01 — Arquitectura funcional

Es agnóstica de framework: SwiftUI, Compose, Flutter, React Native o no-code con REST, almacenamiento seguro y deep links.

## Capas

UI; estado; dominio; datos/API/caché; integraciones de deep links, compartir y notificaciones futuras.

## Estado mínimo

token, member, locale, timezone, branding_cache, pending_action, wallet_cache, qr_cache y last_sync_at. Guardar tokens en Keychain o equivalente cifrado.

## Bootstrap

1. Splash con branding cacheado/fallback.
2. Refrescar mobile/branding sin bloquear.
3. Validar token con GET /member o /member/identity.
4. Válido → Descubrir; ausente/401 → Login.
5. Procesar deep link pendiente tras autenticar.

El modo anónimo existe detrás de configuración pero no se activa sin decisión explícita.

## Sincronización y offline

Refrescar Descubrir y Billetera al entrar; detalle al abrir; Billetera al volver de QR/background. No se requiere realtime V1.

Offline permite abrir QR cacheados con programa, saldo conocido, fecha, indicador Offline y Reintentar. No ejecutar canjes o transferencias offline.

## Deep links

Resolver card, reward, stamp-card, voucher, claim-voucher y request-points. Si requiere auth, conservar destino y retomarlo.
