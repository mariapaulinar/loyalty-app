/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MEMBER BUNDLE - Customer-Facing Experience (~15KB gzipped)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Optimized for mobile-first, PWA-ready customer experiences. Members are
 * your end-users—customers collecting stamps, earning points, claiming rewards.
 * Their experience must be lightning-fast on mobile networks.
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ Member Session          - UUID tracking for anonymous members           │
 * │ PWA QR Cache            - Offline-first QR code caching                │
 * │ Premium Cards           - 3D card interactions and animations          │
 * │ Confetti                - Celebration animations for rewards           │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Moved to core.js (universal dependencies):
 * - QR Code Display         → All roles display QR codes
 * - PIN Input               → All roles use OTP authentication
 * - Forms                   → Account settings forms
 * - Image Upload            → Profile photo upload
 *
 * What Members DON'T need (excluded for performance):
 * - QR Scanner (zxing)     → Members SHOW QR codes, they don't SCAN them
 * - TipTap Editor          → Members don't edit rich content
 * - Pickr Color Picker     → Members don't customize branding
 * - ApexCharts             → Members don't view analytics dashboards
 * - Datepicker             → Members don't need date pickers
 * - AI Integration         → Partner-only feature
 *
 * Performance Budget: <20KB gzipped (mobile-critical)
 *
 * @copyright 2025 NowSquare. All rights reserved.
 */

'use strict';

// ═══════════════════════════════════════════════════════════════════════════
// MEMBER IDENTITY - Anonymous session tracking with UUID
// ═══════════════════════════════════════════════════════════════════════════
// Enables seamless loyalty card following without requiring registration.
// Associates device fingerprints with member accounts.
import './member-session.js';

// ═══════════════════════════════════════════════════════════════════════════
// PWA SUPPORT - Offline-first QR code management
// ═══════════════════════════════════════════════════════════════════════════
// Caches QR codes in IndexedDB for instant offline display.
// Critical for in-store experiences with spotty connectivity.
import './pwa-qr-cache.js';

// ═══════════════════════════════════════════════════════════════════════════
// PREMIUM CARDS - 3D interactions for loyalty cards
// ═══════════════════════════════════════════════════════════════════════════
// Tilt effects, shine animations, and haptic feedback for Apple Wallet-style
// card interactions. Pure CSS/JS—no heavy 3D libraries.
import './premium-cards.js';

// ═══════════════════════════════════════════════════════════════════════════
// CELEBRATION ANIMATIONS - Confetti for successful interactions
// ═══════════════════════════════════════════════════════════════════════════
// Members see confetti when claiming rewards, completing stamp cards, etc.
// Creates "Disney Castle Moments" that drive engagement.
import './confetti.js';
