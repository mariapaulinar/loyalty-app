# Mobile Specs — Loyalty Member y Loyalty Staff

Fuente de verdad para **dos apps nativas independientes** con **paridad de flujo y look & feel** al PWA Member/Staff de producción en `loyalty-app`.

| App | Plataformas | Stack |
|-----|-------------|--------|
| Loyalty Member | Android + iOS | Kotlin/Compose · Swift/SwiftUI |
| Loyalty Staff | Android + iOS | Kotlin/Compose · Swift/SwiftUI |
| Backend | — | Laravel `/api/{locale}/v1/member\|staff` + `/mobile/branding` |

Repos: `loyalty-member-android|ios` · `loyalty-staff-android|ios`.

## Cómo usar con un agente

1. [`AGENTS.md`](AGENTS.md) → [`00-product-context.md`](00-product-context.md) → [`01-architecture.md`](01-architecture.md)
2. Confirmar app de la TASK (`member` | `staff`)
3. [`02-design-system.md`](02-design-system.md) + [`06-pwa-component-parity.md`](06-pwa-component-parity.md) + [`05-screen-map.md`](05-screen-map.md)
4. TASK en `tasks/`; screen/flow concretos
5. Si falta API → [`backend-gaps.md`](backend-gaps.md); **no inventar**

### Prompt plantilla

```
Eres un agente de implementación para Loyalty.
App: MEMBER | STAFF (una sola).
Lee docs/mobile/AGENTS.md, 05-screen-map.md, 06-pwa-component-parity.md
y docs/mobile/tasks/TASK-XXX-....md.
Paridad PWA producción: top nav Member (Home + My Cards); QR solo en detalle de programa (URL staff).
No inventes TabBar con tab QR ni endpoints.
Tema: paint-first + GET /mobile/branding.
Repos: loyalty-member-* o loyalty-staff-*.
```

Prompt listo TASK-020: [`prompts/CODEX-TASK-020-member-android-auth.md`](prompts/CODEX-TASK-020-member-android-auth.md)

## Índice

| Doc | Contenido |
|-----|-----------|
| [AGENTS.md](AGENTS.md) | Reglas |
| [00-product-context.md](00-product-context.md) | Producto |
| [01-architecture.md](01-architecture.md) | Arquitectura |
| [02-design-system.md](02-design-system.md) | Branding + tokens fallback |
| [03-api-contract.md](03-api-contract.md) | REST |
| [04-auth-and-sessions.md](04-auth-and-sessions.md) | Auth |
| [05-screen-map.md](05-screen-map.md) | Pantallas + nav + contrato QR |
| [06-pwa-component-parity.md](06-pwa-component-parity.md) | Blade → nativo |
| [07-navigation-shell.md](07-navigation-shell.md) | Chrome PWA → shell nativo |
| [08-implementation-status.md](08-implementation-status.md) | Auditoría PWA ↔ código (gap matrix) |
| [screens/](screens/) | Spec por pantalla |
| [flows/](flows/) | Flujos E2E |
| [tasks/](tasks/) | Backlog |
| [backend-gaps.md](backend-gaps.md) | Gaps |
| [acceptance.md](acceptance.md) | Checklist |
| [design-tokens.json](design-tokens.json) | Fallback estático |
| [fixtures/](fixtures/) | JSON ejemplos |
| [prompts/](prompts/) | Prompts Cursor/Agent |

## Fases

```
F0 Specs → F1 Backend → F2 Scaffold → F3 Auth → F4 Member core
→ F5 Staff core → F6 UI polish → F7 QA
```

## Fuera de alcance V1

Partner/Admin, RN/Flutter/Capacitor, app única con Role Gate, Agent API como transporte.
