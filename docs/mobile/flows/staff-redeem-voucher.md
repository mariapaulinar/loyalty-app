# Flow: Staff redeem voucher

**ID:** `FLOW_S_VOUCHER`  
**App:** Loyalty Staff (solo)  
**Screens:** `S_VoucherRedeem` (± member from scan)  
**API:** vouchers/validate, vouchers/{id}/redeem

## Pasos

1. Staff ingresa o escanea código voucher (y member si aplica).
2. `POST /staff/vouchers/validate`.
3. Mostrar validez / restricciones.
4. Confirmar → `POST /staff/vouchers/{voucherId}/redeem`.
5. Confirmación éxito.

## DoD

Voucher no validable dos veces si single-use (según reglas backend).
