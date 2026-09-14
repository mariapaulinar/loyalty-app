# M_DeviceCode

**PWA:** switch account / identity fields (`device_code`); `identity-badge` existe pero **no** está en el chrome del layout actual.

## Rol

Pantalla **secundaria** (desde Profile / Switch): mostrar `device_code` corto para sync multi-device y datos de `GET /member/identity`.

**No** es el QR de caja. El QR de caja vive en `M_CardDetail` / `M_StampDetail` / `M_VoucherDetail`.

## UI

Código mono + copy; opcional QR del `unique_identifier` solo como ayuda de sync — no reemplaza deep-link staff.

## API

`GET /member/identity` (cache offline ok).

## DoD

Accesible desde Profile/Switch; **no** aparece como tab de navegación principal.

---

### Nota migración

El antiguo ID `M_QrIdentity` (tab QR) queda **deprecado**. No implementar TabBar QR.
