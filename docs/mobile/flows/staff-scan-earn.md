# Flow: Staff scan → earn points

**ID:** `FLOW_S_SCAN_EARN`  
**App:** Loyalty Staff  
**Screens:** `S_Home` → `S_Scanner` → `S_EarnPoints` (directo)  
**PWA:** QR member = URL `staff.earn.points` → redirect inmediato a add points

## Pasos

1. Staff en Home → Scan.
2. Cámara decodifica QR del Member (generado en `M_CardDetail`).
3. Si URL earn válida → abrir `S_EarnPoints` con member + card del path (sin hub intermedio).
4. Confirmar amount/points → API purchase.
5. Éxito → toast + preferible historial member/card (P2) o volver a Home.

## Fallback

QR no es URL / identifier suelto → `S_MemberLookup` → elegir card → Earn.

## DoD

Mismo happy path que Staff PWA: scan → pantalla de acción.
