# Flow: Member follow card and view balance

**ID:** `FLOW_M_FOLLOW_CARD`  
**App:** Loyalty Member (solo)  
**Screens:** `M_Home` / `M_MyCards` → `M_CardDetail`  
**API:** all-cards, follow (G-MEM-02), balance, card detail (G-MEM-01)

## Pasos

1. Usuario abre card desde Home.
2. `GET /member/cards/{id}` (detalle).
3. CTA Follow → `POST .../follow`.
4. Mostrar balance `GET /member/balance/{cardId}`.
5. Unfollow → `DELETE .../follow`.
6. Card aparece en My Cards followed.

## DoD

Follow refleja en UI y en `followed-cards` tras refresh.
