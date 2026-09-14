# 06 — Paridad PWA

Paridad conserva información, orden, reglas y estados; no copia HTML.

| Dominio | Fuente |
|---|---|
| Shell | member/layouts/default.blade.php |
| Descubrir | member/home*.blade.php |
| Billetera | member/my-cards.blade.php + PageController@dashboard |
| Puntos/rewards | member/card/* |
| Sellos | member/stamp-card/index.blade.php |
| Cupones | member/voucher/index + member/vouchers/claim |
| Auth | member/auth/* |
| Cuenta | account/privacy-data, switch-account y definición account |
| Secundarias | member/code, referrals, point_request |
| Offline | pwa/offline.blade.php |

## Reglas

Descubrir muestra loyalty/vouchers/stamps activos. Billetera ordena sellos → puntos → niveles → cupones y oculta secciones sin datos. Detalles conservan sus tabs. QR usa isla blanca, identificador e instrucción. Contacto, compartir y añadir/quitar son secundarios. Mostrar elegibilidad/vencimiento antes del canje.

No replicar menús de otros roles, Agent Keys, enlaces para negocios, acciones comentadas como navegación primaria ni bugs/botones duplicados.
