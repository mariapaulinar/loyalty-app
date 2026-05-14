/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ADMIN BUNDLE - Platform Administration (~60KB gzipped)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * For platform administrators who manage partners, networks, and settings.
 * Admins need analytics to monitor platform health but don't create
 * marketing content like partners do.
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ ApexCharts              - Analytics dashboards for platform metrics    │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Moved to core.js (universal dependencies):
 * - Forms                   → Partner/admin CRUD operations
 * - Image Upload            → Partner logos, admin avatars
 * - QR Code Display         → Viewing partner QR configurations
 * - Datepicker              → Date filters for activity logs, reports
 * - PIN Input               → OTP authentication
 *
 * What Admins DON'T need (excluded for performance):
 * - TipTap Editor          → Admins don't create marketing content
 * - AI Integration         → Partner-only feature
 * - QR Scanner (zxing)     → Only staff scan codes
 * - Confetti               → Staff celebration feature
 * - Member Session         → Different auth systems
 * - PWA QR Cache           → Customer PWA feature
 * - Premium Cards          → Partner/member feature
 *
 * Performance Budget: <70KB gzipped (lean admin experience)
 *
 * @copyright 2025 NowSquare. All rights reserved.
 */

'use strict';

// ═══════════════════════════════════════════════════════════════════════════
// COLOR PICKER - Simonwep Pickr
// ═══════════════════════════════════════════════════════════════════════════
// Admin settings uses the shared color input component for branding and PWA
// colors, so the admin bundle needs the same initializer as partner pages.
import './pickr.js';

// ═══════════════════════════════════════════════════════════════════════════
// ANALYTICS CHARTS - ApexCharts
// ═══════════════════════════════════════════════════════════════════════════
// Platform-level analytics: partner growth, transaction volumes,
// activity logs visualization. Declarative charting with data attributes.
import './analytics.js';
