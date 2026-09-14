# S_Splash

**App:** Loyalty Staff  
**PWA:** carga inicial staff layout  

## UI

Logo branding (`logo_url` / dark), fondo `pwa_background_color` o tokens, spinner breve.

## Comportamiento

1. Config + tema paint-first (disk cache o `design-tokens.json`).
2. Paralelo: `GET /mobile/branding` (ETag) + validar token Staff en secure storage.
3. Token válido → `S_Home`; si no → `S_Login`.
4. Persist branding 200; aplicar si `version` cambió.

**No** Role Gate ni UI Member.

## DoD

Arranque a Home o Login Staff; tema white-label sin bloquear login.
