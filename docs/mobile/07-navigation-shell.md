# 07 — Navegación y shell

## Nativo

Bottom navigation: Descubrir, Billetera y Cuenta. Es una adaptación táctil del modelo existente. No añadir QR.

Top bar: logo, título/back, compartir cuando aplique y avatar opcional.

Jerarquía: Descubrir o Billetera → detalle; Cuenta → perfil/localización/privacidad/secundarias. Reward depende de Card detail. QR se abre como sheet full-screen desde detalle.

## Deep links

card/{id}; card/{cardId}/{rewardId}; stamp-card/{id}; voucher/{id}; claim-voucher/{batchId}/{token}; request-points/{identifier}. Sin auth → Login/Registro → destino original.

Cerrar QR vuelve al detalle. Canje exitoso vuelve al detalle actualizado o Billetera. Evitar duplicar detalles en el stack.

Cuenta incluye perfil, idioma, timezone, privacidad, introducir código, legales y logout. Referidos/solicitud de puntos pueden activarse V1.1.
