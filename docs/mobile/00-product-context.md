# 00 — Contexto del producto Member

Lealmi es una billetera de fidelización multi-negocio. Un miembro conserva tarjetas de puntos, saldos, recompensas, niveles, tarjetas de sellos y cupones de distintos clubs.

## Objetivo

Descubrir programas, agregarlos a la billetera y usar beneficios presencialmente mostrando al personal un QR.

## Continuidad multiplataforma

La app nativa extiende la experiencia Member existente; no constituye un rediseño independiente. El miembro debe poder pasar de la PWA a iOS o Android sin reaprender:

- dónde descubrir y guardar beneficios;
- cómo reconocer tarjetas, sellos, niveles y cupones;
- cómo acumular y canjear mostrando su QR al staff;
- qué ocurre después de autenticarse o completar una acción;
- cómo se representan vigencia, elegibilidad, saldo, progreso y errores.

La coherencia se evalúa por el modelo mental, las reglas y el resultado del journey, no por una copia literal del HTML. Los patrones propios de iOS y Android son válidos cuando mantienen esa continuidad.

## Superficies principales

1. Descubrir: catálogo activo de puntos, sellos y cupones.
2. Billetera: productos seguidos, guardados, reclamados o inscritos.
3. Cuenta: perfil, localización, privacidad y sesión.

## V1 recomendado

Splash, login híbrido, registro OTP, Descubrir, My Cards, detalles de puntos/recompensas/sellos/cupones, QR contextuales, perfil, privacidad, deep links y offline útil.

## Secundarias

Código de cuatro dígitos, referidos, solicitar/enviar puntos y contenido legal. No ocupan la navegación principal.

## Fuera de alcance

Admin, Partner, Staff/POS, Agent API, billing y administración de campañas.
