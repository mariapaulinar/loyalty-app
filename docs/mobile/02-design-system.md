# 02 — Sistema visual Lealmi

## Color

Usar GET /api/{locale}/v1/mobile/branding. El payload dinámico prevalece.

| Token | Fallback |
|---|---|
| brand | #FCD34D |
| foreground_on_brand | #000000 |
| background | #FFFFFF |
| app_name | Lealmi |

Los colores del comercio/tarjeta se aplican a ese producto, no al chrome global.

## Tipografía y layout

UI: Geist, Inter o sistema. Códigos: JetBrains Mono o mono del sistema.

Frames: iOS 390×844; Android 412×915. Grid 4 px; espacios 8/12/16/24/32/48; margen 16; toque mínimo 44×44; tarjeta premium 1.586:1; radios 12 controles, 16 cards, 20–24 sheets.

## Semántica

Oro para marca/CTA; verde éxito; ámbar advertencia; rojo error/vencido; morado cupones; verde sellos; neutros para superficies.

## Componentes

Navigation Bottom/TopBar; LoyaltyCard; StampCard; VoucherCard; MetricTile; TierProgress; RewardTile; Tabs; Button; Input; OtpInput; QrSheet; ConfirmationSheet; Empty/Error/OfflineState; Toast; BusinessContact; ShareAction; MediaCarousel.

## Paint-first

Aplicar cache/fallback antes del primer frame, refrescar con ETag en paralelo y nunca bloquear Home o consultar branding por pantalla.
