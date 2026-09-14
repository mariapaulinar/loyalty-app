# TASK-004 — Card detail + follow/unfollow API

**Platform:** backend  
**Phase:** F1  
**depends_on:** TASK-002  
**Gaps:** G-MEM-01, G-MEM-02

## Objetivo
`GET /member/cards/{cardId}`, `POST|DELETE /member/cards/{cardId}/follow` alineados a web CardController.

## Contexto
- `app/Http/Controllers/Member/CardController.php`
- `app/Http/Controllers/Api/MemberCardController.php`

## DoD
Follow aparece en followed-cards; detail incluye rewards básicos.

