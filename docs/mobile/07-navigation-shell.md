# 07 — Navegación y shell

## Nativo

Bottom navigation: Descubrir, Billetera y Cuenta. Es una adaptación táctil del modelo existente. No añadir QR.

La navegación inferior aparece únicamente en las superficies principales autenticadas. Debe ocultarse durante splash, login, registro, OTP, recuperación, confirmaciones críticas y presentación de QR. Estas pantallas usan back o cerrar según el contexto y nunca simulan destinos disponibles antes de autenticar.

Top bar: logo, título/back, compartir cuando aplique y avatar opcional.

Jerarquía: Descubrir o Billetera → detalle; Cuenta → perfil/localización/privacidad/secundarias. Reward depende de Card detail. QR se abre como sheet full-screen desde detalle.

## Acceso rápido V1 — solo frontend

Para reducir pasos en caja sin crear un QR universal:

- toda tarjeta de Billetera es tocable;
- cada tarjeta puede mostrar una acción secundaria visible “Usar tarjeta” o “Mostrar QR” cuando el contexto sea inequívoco;
- Billetera presenta “Usados recientemente” cuando existe historial local;
- la app guarda localmente el último producto abierto y orden de uso reciente;
- Descubrir incluye acceso visible “Abrir mi billetera”;
- ninguna optimización omite validaciones de elegibilidad, vigencia o saldo.

El acceso rápido no cambia el QR ni la operación: abre el mismo detalle o sheet contextual definido por la PWA.

## Deep links

card/{id}; card/{cardId}/{rewardId}; stamp-card/{id}; voucher/{id}; claim-voucher/{batchId}/{token}; request-points/{identifier}. Sin auth → Login/Registro → destino original.

El socio puede compartir o exhibir un QR que contenga uno de estos enlaces para abrir directamente su producto. El miembro lo escanea con la cámara del teléfono; la app Member no incorpora un escáner universal.

Cerrar QR vuelve al detalle. Al volver del QR, la app refresca el recurso mediante los endpoints existentes o al recuperar foreground. Solo muestra “completado” cuando el backend confirma el nuevo estado; en caso contrario muestra “Esperando confirmación” y permite reintentar/actualizar.

Canje exitoso vuelve al detalle actualizado o Billetera. Evitar duplicar detalles en el stack.

Cuenta incluye perfil, idioma, timezone, privacidad, introducir código, legales y logout. Referidos/solicitud de puntos pueden activarse V1.1.
