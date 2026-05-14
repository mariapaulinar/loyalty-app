/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CORE BUNDLE - Universal Foundation (~60KB gzipped)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The bedrock of all pages. Every user—member, staff, partner, admin—loads
 * this bundle. It contains the absolute essentials that make the app work:
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ Alpine.js + Collapse    - Reactive components, dropdowns, modals       │
 * │ HTMX                    - Server-driven interactivity without JS bloat │
 * │ Lucide Icons            - Beautiful, tree-shakeable SVG icons          │
 * │ Flag Icons              - Country flags for language selectors         │
 * │ Utility Functions       - Theme toggle, helpers, formatters            │
 * │ Localization            - i18n, date formatting, currency              │
 * │ Flowbite/TW Elements    - UI component libraries                       │
 * │ Forms                   - Input validation, phone formatting           │
 * │ Image Upload            - Drag-and-drop image handling                 │
 * │ QR Code Display         - Generate QR codes (all roles display these)  │
 * │ Datepicker              - Flatpickr for CRUD date fields               │
 * │ PIN Input               - OTP verification (all roles have OTP auth)   │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Design Philosophy:
 * - Universal features belong here—if ALL roles need it, it's core.
 * - OTP verification is universal (member, staff, partner, admin auth).
 * - QR codes are displayed by all roles (not just generated).
 * - CRUD forms with images are used by all dashboard roles.
 *
 * Bundle Size Target: <70KB gzipped
 *
 * @copyright 2025 NowSquare. All rights reserved.
 */

'use strict';

// ═══════════════════════════════════════════════════════════════════════════
// CORE UTILITIES - Theme, helpers, formatters
// ═══════════════════════════════════════════════════════════════════════════
import './utility.js';

// ═══════════════════════════════════════════════════════════════════════════
// LOCALIZATION - i18n, date formatting, currency
// ═══════════════════════════════════════════════════════════════════════════
import './localization.js';

// ═══════════════════════════════════════════════════════════════════════════
// FORM HANDLING - Input validation and formatting
// ═══════════════════════════════════════════════════════════════════════════
// Phone number formatting, input masks, validation feedback.
// Used by ALL roles in their respective CRUD interfaces.
import './forms.js';

// ═══════════════════════════════════════════════════════════════════════════
// IMAGE UPLOAD - Premium drag-and-drop
// ═══════════════════════════════════════════════════════════════════════════
// Profile images, avatars, and various image uploads across all roles.
import './image-upload.js';

// ═══════════════════════════════════════════════════════════════════════════
// QR CODE DISPLAY - Generate codes for all contexts
// ═══════════════════════════════════════════════════════════════════════════
// Members show their QR for staff to scan и partners generate QR for marketing.
// Universal need across all user types.
import './qrcode.js';

// ═══════════════════════════════════════════════════════════════════════════
// DATE PICKER - Flatpickr for date/time selection
// ═══════════════════════════════════════════════════════════════════════════
// All dashboard roles (staff, partner, admin) use date pickers in CRUD forms.
// Campaign scheduling, date filters, expiration dates, etc.
// Fully localized with premium dark mode styling.
import './datepicker.js';

// ═══════════════════════════════════════════════════════════════════════════
// PIN INPUT - OTP verification component
// ═══════════════════════════════════════════════════════════════════════════
// ALL roles use OTP for authentication:
// - Member: login/register verification
// - Staff: OTP login verification
// - Partner: login/register verification
// - Admin: OTP login verification
import './pin-input.js';

// ═══════════════════════════════════════════════════════════════════════════
// ALPINE.JS - The reactive backbone
// ═══════════════════════════════════════════════════════════════════════════
// Alpine must be initialized AFTER any Alpine.data() registrations from
// other modules (like TipTap). Since TipTap is only in dashboard bundle,
// and core loads first, this ordering is critical.
import './alpine/alpine.js';

// ═══════════════════════════════════════════════════════════════════════════
// UI COMPONENT LIBRARIES - Pre-built interactive components
// ═══════════════════════════════════════════════════════════════════════════
import './tw-elements/tw-elements.js';
import './flowbite/flowbite.js';

// ═══════════════════════════════════════════════════════════════════════════
// ICON SYSTEM - Lucide for UI, Flags for languages
// ═══════════════════════════════════════════════════════════════════════════
import './lucide.js';
import 'flag-icons';

// ═══════════════════════════════════════════════════════════════════════════
// HTMX - Server-driven interactivity
// ═══════════════════════════════════════════════════════════════════════════
import 'htmx.org';
