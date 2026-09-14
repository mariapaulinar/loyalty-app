# 11 — Especificación de gaps API Member para Cursor

## Objetivo

Completar la paridad del API necesaria para construir el journey Member V1 aprobado en 09 y 10, reutilizando las reglas de negocio de la PWA y sin modificar su comportamiento.

Este documento es una instrucción ejecutable para un agente de Cursor. La fuente de verdad sigue siendo el código activo de la PWA. No usar controladores Agent, Partner, Staff o POS como contrato móvil.

## Contexto técnico comprobado

- Base: `/api/{locale}/v1`.
- Autenticación protegida: guard y middleware existentes de `member_api`.
- Token: Sanctum.
- Framework: Laravel 13, PHP 8.3+, Sanctum 4.
- Ya existen: auth híbrido/OTP, identidad Member, tarjetas, balance, follow, sellos, inscripción, cupones guardados y canje de código.
- Mantener `es-es` y el envelope definido aquí.
- Los endpoints nuevos son aditivos: no romper responses móviles existentes.

## Definition of Done global

Cada gap debe incluir:

1. ruta en `routes/api.php` dentro de `{locale}/v1/member` y `auth:member_api`;
2. Form Request cuando reciba body o query no trivial;
3. controlador Member API, nunca Agent;
4. API Resources explícitos; no devolver modelos Eloquent crudos;
5. reglas extraídas/reutilizadas desde Services o Actions compartidas con la PWA;
6. eager loading y límite explícito de colecciones;
7. traducciones `es-es`;
8. OpenAPI;
9. pruebas Feature Pest para 200/400/401/403/404/409/422 y reglas propias;
10. fixtures/factories representativos;
11. logs sin token, OTP, firma, email completo o datos sensibles;
12. índices revisados con `EXPLAIN` para consultas agregadas;
13. compatibilidad con Laravel Cloud y filesystem efímero.

## Convención de response

Éxito singular:

```json
{
  "data": {},
  "meta": {
    "generated_at": "2026-09-14T17:00:00Z"
  }
}
```

Colección:

```json
{
  "data": [],
  "meta": {
    "generated_at": "2026-09-14T17:00:00Z",
    "next_cursor": null
  }
}
```

Error de negocio:

```json
{
  "message": "La recompensa ya no está disponible.",
  "code": "reward_unavailable",
  "errors": {}
}
```

Códigos estables en `snake_case`; los textos se traducen por locale.

---

# P0 — necesarios para el journey visual

## GAP-M01 — Descubrir agregado

### Endpoint

`GET /api/{locale}/v1/member/discover`

Autorización V1: `auth:member_api`.

Query opcional:

| Campo | Tipo | Regla |
|---|---|---|
| `cursor` | string | cursor opaco |
| `limit` | int | 1–50; default 20 |
| `types[]` | enum[] | loyalty, stamp, voucher |
| `club_id` | string | club activo y visible |

### Regla fuente

Reproducir `Member\PageController@index` y variantes `member/home*.blade.php`:

- solo partner/club/programa activo;
- respetar visibilidad y configuración de portada;
- vencimiento próximo antes de fecha de creación;
- no devolver recursos internos, draft, pausados o fuera de elegibilidad;
- Directory es referencia funcional V1;
- Showcase/Portal se expresan en `meta.layout`, no duplican endpoints.

### Response mínimo

```json
{
  "data": {
    "hero": {
      "title": "Beneficios cerca de ti",
      "subtitle": "Encuentra tu próximo favorito"
    },
    "sections": [
      {
        "type": "loyalty",
        "title": "Programas",
        "items": [
          {
            "id": "uuid",
            "type": "loyalty",
            "name": "Ramona Waffles",
            "summary": "1 punto por visita",
            "image_url": "https://...",
            "brand_color": "#111318",
            "club": {
              "id": "uuid",
              "name": "Ramona Waffles",
              "logo_url": "https://..."
            },
            "expires_at": null,
            "is_followed": false,
            "deep_link": "https://.../card/uuid"
          }
        ]
      }
    ]
  },
  "meta": {
    "layout": "directory",
    "generated_at": "..."
  }
}
```

### Implementación sugerida

- `App\Http\Controllers\Api\MemberDiscoverController@index`
- `App\Http\Resources\Member\DiscoverItemResource`
- extraer consulta compartida a `App\Services\Member\MemberDiscoverService`;
- PageController y API deben usar el mismo servicio para evitar divergencia.

### Tests críticos

Activo/visible; partner inactivo; vencido; orden; tipos mezclados; empty; locale; N+1; cursor.

---

## GAP-M02 — Billetera agregada

### Endpoint

`GET /api/{locale}/v1/member/wallet`

Autorización: `auth:member_api`.

### Regla fuente

Reproducir `PageController@dashboard` y `member/my-cards.blade.php`:

- loyalty: tarjetas seguidas/transaccionadas según regla web;
- sellos: inscripciones activas;
- cupones: guardados o reclamados;
- orden de secciones: stamps → loyalty → tier → vouchers;
- omitir secciones sin datos;
- métricas condicionales; no enviar ceros que la PWA oculta;
- progreso y tier calculados con la misma lógica web.

“Usados recientemente” no pertenece al API: se calcula localmente en la app y no debe alterar esta respuesta.

### Response mínimo

```json
{
  "data": {
    "greeting_context": {
      "member_name": "Pao",
      "timezone": "America/Bogota"
    },
    "metrics": [
      {"key": "points", "value": 420, "label": "Puntos"},
      {"key": "programs", "value": 3, "label": "Programas"}
    ],
    "sections": {
      "stamp_cards": [],
      "loyalty_cards": [],
      "tier_progress": [],
      "vouchers": []
    }
  },
  "meta": {
    "generated_at": "...",
    "version": 1
  }
}
```

Cada item debe contener `id`, `type`, `name`, `club{id,name,logo_url}`, `brand_color`, estado Member, CTA semántico y `deep_link`. No incluir QR en wallet.

### Implementación sugerida

- `MemberWalletController@show`
- `MemberWalletService`
- Resources separados por tipo;
- reutilizar `getMemberBalance`, `StampCardMember` y relaciones `member->vouchers`;
- resolver agregados en pocas consultas, no dentro de loops.

### Tests críticos

Wallet completa/parcial/vacía; secciones omitidas; saldo cero visible cuando corresponde; progreso conservado; voucher reclamado; tier; aislamiento entre miembros; N+1.

---

## GAP-M03 — Detalle de recompensa

### Endpoint

`GET /api/{locale}/v1/member/cards/{cardId}/rewards/{rewardId}`

### Reglas

- card activo y accesible;
- reward pertenece a card y está activo;
- calcular saldo en servidor;
- devolver costo, faltante y elegibilidad;
- reglas de expiración, stock/límite y tier iguales a la PWA;
- nunca permitir que el cliente decida elegibilidad.

### Response mínimo

```json
{
  "data": {
    "id": "uuid",
    "card_id": "uuid",
    "name": "Waffle clásico",
    "description": "...",
    "media": [{"type": "image", "url": "https://..."}],
    "cost": 300,
    "balance": 420,
    "missing_points": 0,
    "eligibility": {
      "eligible": true,
      "code": "eligible",
      "message": null
    },
    "expires_at": null,
    "club": {"id": "uuid", "name": "Ramona Waffles", "logo_url": "https://..."}
  },
  "meta": {"generated_at": "..."}
}
```

### Errores

404 card/reward no encontrado; 409 `reward_unavailable`; 403 `reward_not_eligible`.

---

## GAP-M04 — Claim idempotente y QR firmado de recompensa

### Endpoint

`POST /api/{locale}/v1/member/cards/{cardId}/rewards/{rewardId}/claim`

Headers:

- `Authorization: Bearer ...`
- `Idempotency-Key: UUID` obligatorio.

Body:

```json
{"client_reference": "uuid-generado-por-app"}
```

### Transacción obligatoria

1. iniciar DB transaction;
2. bloquear Member/card/reward o registro de disponibilidad necesario;
3. recalcular saldo y elegibilidad;
4. buscar claim previo por Member + idempotency key;
5. crear claim pendiente;
6. reservar/deducir según la regla actual de la PWA, no crear una regla nueva;
7. generar URL firmada con expiración;
8. commit;
9. devolver el mismo claim ante reintento idéntico.

### Response 201/200 idempotente

```json
{
  "data": {
    "claim_id": "uuid",
    "status": "pending_staff_confirmation",
    "reward": {"id": "uuid", "name": "Waffle clásico", "cost": 300},
    "balance_before": 420,
    "balance_after_confirmation": 120,
    "qr": {
      "type": "reward_claim",
      "url": "https://.../staff/...",
      "expires_at": "...",
      "refreshable": true
    }
  },
  "meta": {"generated_at": "..."}
}
```

### Estados

`pending_staff_confirmation`, `confirmed`, `expired`, `cancelled`.

La app no muestra éxito por recibir 201. Muestra “Esperando confirmación” hasta que una consulta posterior observe `confirmed`.

### Seguridad

- URL firmada por Laravel; no exponer IDs secuenciales sin firma;
- TTL configurable, sugerido 5 minutos;
- un claim confirmado no puede reutilizarse;
- firma vinculada a claim, Member y operación;
- rate limit por Member y reward;
- loguear claim_id y resultado, nunca la URL firmada completa.

---

## GAP-M05 — QR oficiales en detalles existentes

No crear un QR universal ni hacer que el cliente construya URLs.

Extender responses existentes de detalle con un objeto `qr` o `actions[].qr`:

### Loyalty card

```json
{
  "qr": {
    "type": "earn_points",
    "url": "https://...",
    "expires_at": null,
    "instruction": "Muéstralo al personal"
  }
}
```

### Stamp card

```json
{
  "actions": {
    "receive_stamp": {
      "enabled": true,
      "qr": {"type": "receive_stamp", "url": "https://...", "expires_at": null}
    },
    "redeem_reward": {
      "enabled": true,
      "pending_rewards": 1,
      "qr": {"type": "stamp_reward", "url": "https://...", "expires_at": "..."}
    }
  }
}
```

### Voucher

```json
{
  "actions": {
    "use_voucher": {
      "enabled": true,
      "code": "RAMONA20",
      "qr": {"type": "voucher_redemption", "url": "https://...", "expires_at": "..."}
    }
  }
}
```

Cada acción devuelve `enabled`, `disabled_code` y `disabled_message`. El backend decide expirado, agotado, usado, no elegible o desactivado.

---

## GAP-M06 — Estado posterior al QR sin tiempo real

V1 no requiere endpoint nuevo si M03, card detail, stamp detail y voucher detail devuelven el estado actual.

Comportamiento esperado:

- app recupera foreground o cierra QR;
- invalida caché local del recurso;
- llama nuevamente el endpoint de detalle;
- compara `claim/status`, saldo, sellos, pending_rewards o voucher status;
- solo entonces muestra éxito.

Opcional si el detalle no permite identificar la operación:

`GET /api/{locale}/v1/member/claims/{claimId}`

```json
{"data":{"id":"uuid","status":"confirmed","confirmed_at":"..."}}
```

No implementar WebSockets/Reverb para V1.

---

# P1 — completar pantallas del catálogo

## GAP-M07 — Historial de puntos

`GET /member/cards/{cardId}/history?cursor=&limit=20`

Tipos normalizados: purchase, earn, adjustment, transfer, redeem, expiry. Cursor obligatorio para crecimiento.

## GAP-M08 — Historial y estado de cupón

`GET /member/vouchers/{voucherId}/history`

Devolver saved, claimed, redeemed, voided y expirado con timestamps públicos.

## GAP-M09 — Claim de campaña de cupón

`POST /member/voucher-campaigns/{batchId}/claim`

Token/código en body o deep link, idempotencia, límites personales/globales y errores 409 estables: already_claimed, exhausted, paused, expired, not_eligible.

## GAP-M10 — Perfil y localización

- `PATCH /member/profile`
- `PATCH /member/preferences`
- cambios sensibles mediante challenge OTP existente;
- campos: name, marketing_consent, locale, timezone;
- no aceptar email sin verificación.

## GAP-M11 — Privacidad y datos

- exportación;
- eliminar relación con club;
- eliminar cuenta;
- confirmación OTP y job asíncrono solo para exportación pesada/eliminación diferida.

## GAP-M12 — Contacto normalizado

Todos los Resources de club exponen únicamente canales públicos:

```json
{"contact":{"phone":null,"whatsapp":null,"email":null,"website":null,"address":null,"maps_url":null}}
```

---

# Fuera de V1

Referidos, solicitar/enviar puntos, reseñas, geofencing y notificaciones de proximidad permanecen P2. No bloquear el journey actual por estos módulos.

---

# Laravel Cloud

## Infraestructura mínima P0

Los endpoints de lectura y claim pueden funcionar de forma síncrona con el app compute y la base de datos actuales. Para P0 no son obligatorios queues, scheduler, WebSockets ni nuevo object storage.

### Variables y secretos

Configurar por environment y nunca commitear:

```dotenv
MEMBER_QR_TTL_MINUTES=5
MEMBER_API_CACHE_TTL_SECONDS=30
MOBILE_DEEP_LINK_BASE_URL=https://lealmi.com
MEMBER_CLAIM_RATE_LIMIT_PER_MINUTE=10
```

La firma Laravel usa `APP_KEY`; debe permanecer estable entre deploys y réplicas. Valores sensibles se administran como Secrets de Laravel Cloud y requieren redeploy tras cambios.

### Cache

Recomendado, no obligatorio inicialmente:

- Laravel Valkey adjunto al environment para rate limiting distribuido, idempotencia temporal y cache de Discover;
- Laravel Cloud inyecta `CACHE_STORE`, `REDIS_HOST` y `REDIS_PASSWORD` al adjuntarlo;
- no usar file cache: el filesystem de los workers/app es efímero y no compartido;
- wallet por Member: cache corto o sin cache; invalidar después de follow/save/enroll/claim/operaciones Staff.

### Queue

No usar queue para crear claim o generar la URL: la respuesta debe ser inmediata y transaccional.

Usar queue solo para email OTP, exportación de datos, eliminación diferida o notificaciones. Si se activa Managed Queue:

- el proyecto ya usa Laravel 13 y `aws/aws-sdk-php`, compatibles con la generación actual;
- Laravel Cloud establece `QUEUE_CONNECTION=cloud`;
- revisar el Horizon existente: Managed Queues no funciona con Horizon. No cambiar de Redis/Horizon a Cloud Queue sin decidir una sola estrategia;
- para V1 y bajo volumen, conservar la configuración actual si OTP ya funciona.

### Scheduler

No requerido para firmas stateless: validar expiración en cada request. Activarlo solo para purgar claims expirados, limpiar tokens o tareas ya programadas. En Laravel Cloud se habilita con el toggle Scheduler y ejecuta `schedule:run` cada minuto.

### Object Storage

Logos/media deben usar URLs persistentes. Si hoy dependen de disco local, adjuntar Laravel Object Storage y usar `Storage`; el proyecto ya incluye `league/flysystem-aws-s3-v3`. No guardar media en filesystem efímero.

### WebSockets

No requeridos en V1. El estado “Esperando confirmación” usa refresh al foreground/manual. Reverb queda como mejora posterior si se necesita actualización instantánea.

### Preview y despliegue

- usar Preview Environment del PR para ejecutar tests API y smoke tests;
- correr migraciones con estrategia backward-compatible;
- desplegar Resources/endpoints antes de habilitar las pantallas;
- observar logs, 4xx/5xx, latencia p95 y consultas;
- rollback: feature flag `FEATURE_MEMBER_MOBILE_V1=false` si se decide proteger el rollout.

Referencias oficiales:

- https://laravel.com/cloud/docs/queues
- https://laravel.com/cloud/docs/scheduled-tasks
- https://laravel.com/cloud/docs/secrets
- https://laravel.com/cloud/docs/resources/caches/valkey
- https://laravel.com/cloud/docs/resources/object-storage

---

# Orden de implementación para Cursor

## PR 1 — Resources y servicios compartidos

- crear Resources Member;
- extraer reglas de Discover/Wallet a Services;
- mantener snapshots de response;
- sin cambiar rutas web visibles.

## PR 2 — Discover y Wallet

- M01 y M02;
- índices;
- OpenAPI y pruebas.

## PR 3 — Reward detail y claim

- M03 y M04;
- migración claims/idempotency si no existe entidad reutilizable;
- transacciones y concurrency tests.

## PR 4 — QR por producto y refresh

- M05;
- ampliar Resources existentes;
- confirmar M06 sin WebSockets.

## PR 5 — P1

- M07–M12 por capability, no como PR monolítico.

---

# Prompt inicial para Cursor

```text
Trabaja en la rama basada en main del repositorio loyalty-app.

Lee completamente:
- docs/mobile/README.md
- docs/mobile/03-api-contract.md
- docs/mobile/06-pwa-component-parity.md
- docs/mobile/09-member-ui-spec.md
- docs/mobile/10-member-journeys.md
- docs/mobile/11-api-gaps-cursor-spec.md

Implementa únicamente el PR indicado por el usuario. No cambies reglas funcionales de la PWA y no uses Agent/Partner/Staff como contrato Member.

Antes de escribir código:
1. traza cada regla a ruta/controlador/vista/servicio existente;
2. enumera archivos a modificar;
3. identifica migraciones e índices;
4. confirma que el cambio es backward-compatible.

Usa API Resources, Form Requests, Services/Actions compartidos, OpenAPI y pruebas Pest. No devuelvas modelos Eloquent crudos. No construyas QR en el cliente. Claims deben ser transaccionales e idempotentes.

Para Laravel Cloud:
- no dependas del filesystem local;
- no agregues WebSockets, queue o scheduler salvo que este documento lo requiera;
- conserva la estrategia de queue existente;
- documenta variables/secrets y pasos de deploy.

Al terminar ejecuta tests focalizados, Pint y route:list para las rutas Member. Reporta cambios, decisiones, tests y cualquier desviación.
```
