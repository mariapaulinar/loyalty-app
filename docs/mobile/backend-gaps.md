# Gaps de backend — Member

Este archivo es el índice resumido. La especificación implementable, contratos, pruebas, orden de PRs y configuración Laravel Cloud están en [11-api-gaps-cursor-spec.md](11-api-gaps-cursor-spec.md).

## P0

1. GET /member/discover: reproducir PageController@index con loyalty_cards, stamp_cards, vouchers, layout y orden por vigencia.
2. GET /member/wallet: métricas, tarjetas con balance, sellos, cupones, tiers/progreso.
3. GET /member/cards/{cardId}/rewards/{rewardId}: media, costo, saldo, elegibilidad y reglas.
4. POST /member/cards/{cardId}/rewards/{rewardId}/claim: idempotencia, estado pendiente, URL/QR firmado y expiración.
5. qr_payload/qr_url oficiales para puntos, sellos y cupones. El cliente no reconstruye rutas internas o firmas.
6. Confirmación posterior al QR mediante refresh de endpoints existentes; endpoint de claim status solo si el detalle no expone el estado.

## P1

Historial puntos; historial/uso cupón; claim batch; editar perfil con OTP; idioma/timezone; export/delete datos; eliminar relación/cuenta; contacto normalizado; API Resources en vez de modelos crudos.

## P2

Referidos; solicitar/enviar puntos; reseña de sellos; notificaciones/geofencing.

## Infraestructura

P0 no requiere WebSockets, queue ni scheduler. Valkey es recomendado para cache/rate limit distribuido; Object Storage solo si la media depende hoy de disco local. Conservar la estrategia de queue existente y revisar Horizon antes de adoptar Managed Queues.

Todo gap requiere OpenAPI, fixtures, es-es, pruebas 401/403/404/409/422, campos públicos únicamente y firma/expiración en operaciones sensibles.
