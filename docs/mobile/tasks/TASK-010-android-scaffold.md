# TASK-010 — Scaffold Loyalty Member (Android)

**App:** member  
**Platform:** android  
**Phase:** F2  
**depends_on:** none  
**Repo:** `loyalty-member-android` (legacy `loyalty-android` OK si se renombra/`applicationId`)

## Objetivo

Proyecto Kotlin Compose Member-only: networking member, tema tokens, Splash → Home placeholder. `applicationId` = `com.lealmi.loyalty.member`.

## Pasos

1. App shell Hilt + Compose + Navigation.
2. ApiConfig + MemberApi stubs; TokenStore (solo member).
3. Sin RoleGate ni packages staff.
4. README → `docs/mobile/AGENTS.md`.

## DoD

App arranca Splash → Member Home placeholder; no UI Staff.

## No hacer

Staff scanner, login staff, Role Gate.
