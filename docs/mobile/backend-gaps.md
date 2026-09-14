# Gaps de backend — Member

## P0

1. GET /member/discover: reproducir PageController@index con loyalty_cards, stamp_cards, vouchers, layout y orden por vigencia.
2. GET /member/wallet: métricas, tarjetas con balance, sellos, cupones, tiers/progreso.
3. GET /member/cards/{cardId}/rewards/{rewardId}: media, costo, saldo, elegibilidad y reglas.
4. POST /member/cards/{cardId}/rewards/{rewardId}/claim: URL/QR firmado y expiración.
5. qr_payload/qr_url oficiales para puntos, sellos y cupones. El cliente no reconstruye rutas internas o firmas.

## P1

Historial puntos; historial/uso cupón; claim batch; editar perfil con OTP; idioma/timezone; export/delete datos; eliminar relación/cuenta; contacto normalizado; API Resources en vez de modelos crudos.

## P2

Referidos; solicitar/enviar puntos; reseña de sellos; notificaciones/geofencing.

Todo gap requiere OpenAPI, fixtures, es-es, pruebas 401/404/409/422, campos públicos únicamente y firma/expiración en operaciones sensibles.
