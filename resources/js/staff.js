/**
 * ═══════════════════════════════════════════════════════════════════════════
 * STAFF BUNDLE - Point-of-Sale Operations (~70KB gzipped)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Built for cashiers, servers, and frontline staff who interact with customers.
 * Key capability: QR code scanning to award points, stamps, and redeem rewards.
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ QR Scanner (ZXing)      - Camera-based QR code scanning                │
 * │ Confetti                - Celebration animations for redemptions       │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Moved to core.js (universal dependencies):
 * - Forms                   → Input validation and formatting
 * - Image Upload            → Profile/transaction photo capture
 * - QR Code Display         → All roles display QR codes
 * - Datepicker              → Date selection for CRUD forms
 * - PIN Input               → OTP authentication
 *
 * What Staff DON'T need (excluded for performance):
 * - TipTap Editor          → Staff don't create rich content
 * - Pickr Color Picker     → Staff don't customize branding
 * - ApexCharts             → Staff don't view analytics dashboards
 * - AI Integration         → Partner-only feature
 * - Member Session         → Staff have their own auth system
 * - PWA QR Cache           → Staff scan codes, not display them
 * - Premium Cards          → Customer-facing feature only
 *
 * Special Note: ZXing is ~450KB uncompressed but essential for scanning.
 * We accept this cost because scanning IS the staff's primary function.
 *
 * Performance Budget: <80KB gzipped (QR scanner is heavy but essential)
 *
 * @copyright 2025 NowSquare. All rights reserved.
 */

'use strict';

// ═══════════════════════════════════════════════════════════════════════════
// QR SCANNER - Camera-based code detection (ZXing)
// ═══════════════════════════════════════════════════════════════════════════
// The ONLY user type that needs this heavy library. Staff scan member QR codes
// to award stamps, points, and process reward redemptions.
// Uses @zxing/browser + @zxing/library (~450KB uncompressed).
import './qrscanner.js';

// ═══════════════════════════════════════════════════════════════════════════
// CELEBRATION ANIMATIONS - Confetti for successful transactions
// ═══════════════════════════════════════════════════════════════════════════
// "Disney Castle Moments" for reward redemptions. Creates memorable
// customer experiences that drive engagement and repeat visits.
import './confetti.js';
