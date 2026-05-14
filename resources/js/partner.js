/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARTNER BUNDLE - Business Owner Dashboard (~180KB gzipped)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The complete toolkit for business owners who configure loyalty programs,
 * create email campaigns, analyze data, and manage their teams.
 *
 * Partners have the most feature-rich experience with:
 * - Rich text editing for email campaigns
 * - Brand color customization
 * - Analytics dashboards
 * - AI-powered content generation
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ TipTap Editor           - Rich text editing for emails, descriptions   │
 * │ Pickr Color Picker      - Brand color customization                    │
 * │ ApexCharts              - Analytics dashboards with interactive charts │
 * │ Premium Cards           - Card preview in designers                    │
 * │ AI Integration          - OpenAI-powered content generation            │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Moved to core.js (universal dependencies):
 * - Forms                   → Advanced input validation and formatting
 * - Image Upload            → Logo, card images, reward photos
 * - QR Code Display         → Generate codes for marketing materials
 * - Datepicker              → Campaign scheduling, date filters
 * - PIN Input               → OTP authentication
 *
 * What Partners DON'T need (excluded for performance):
 * - QR Scanner (zxing)     → Only staff scan codes
 * - Confetti               → Staff celebration feature
 * - Member Session         → Different auth systems
 * - PWA QR Cache           → Customer PWA feature
 *
 * Performance Budget: <200KB gzipped (feature-rich is expected here)
 *
 * @copyright 2025 NowSquare. All rights reserved.
 */

'use strict';

// ═══════════════════════════════════════════════════════════════════════════
// RICH TEXT EDITOR - TipTap (ProseMirror-based)
// ═══════════════════════════════════════════════════════════════════════════
// CRITICAL: TipTap registers Alpine.data() so it MUST be imported BEFORE
// Alpine.js starts (which happens in core.js after this bundle loads).
// Used for: Email campaign content, reward descriptions, card text.
import './tiptap.js';

// ═══════════════════════════════════════════════════════════════════════════
// COLOR PICKER - Simonwep Pickr
// ═══════════════════════════════════════════════════════════════════════════
// Premium color selection for brand customization.
// Curated swatches, hex input, nano theme for minimal footprint.
import './pickr.js';

// ═══════════════════════════════════════════════════════════════════════════
// ANALYTICS CHARTS - ApexCharts
// ═══════════════════════════════════════════════════════════════════════════
// Declarative charting system with data attributes.
// Line, bar, donut, and multi-series charts with dark mode support.
import './analytics.js';

// ═══════════════════════════════════════════════════════════════════════════
// PREMIUM CARDS - Card preview in designer
// ═══════════════════════════════════════════════════════════════════════════
// Partners preview their loyalty cards with 3D effects.
// WYSIWYG card builder experience.
import './premium-cards.js';

// ═══════════════════════════════════════════════════════════════════════════
// AI INTEGRATION - OpenAI-powered content generation
// ═══════════════════════════════════════════════════════════════════════════
// Auto-generate reward descriptions, email copy, and marketing content.
// Partner-only feature (server validates access).
import './ai.js';
