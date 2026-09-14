# Reward Loyalty

**Infrastructure for the future of customer retention.** A self-hosted, multi-tenant loyalty platform built for real-world checkout: staff-initiated earning, shared wallet architecture, and a mobile-first member experience.

📚 [Documentation](https://rewardloyalty.co/docs) | 🎯 [Live Demo](https://rewardloyalty.co/demo)

### Native mobile apps (Member + Staff)

Agent-ready specs for Android (Kotlin/Compose) and iOS (Swift/SwiftUI) live in [`docs/mobile/`](docs/mobile/README.md). Sibling apps: `loyalty-member-android` / `loyalty-member-ios` and `loyalty-staff-android` / `loyalty-staff-ios`.

---

## What is Reward Loyalty?

Reward Loyalty is a **multi-tenant digital loyalty platform** for brick-and-mortar businesses (and the agencies/operators who serve them). It includes:

- A **member wallet** (web/PWA) for loyalty cards, stamp cards, vouchers, and rewards
- A **staff checkout UI** for scanning/searching members and crediting points/stamps
- A **partner dashboard** for configuring programs, rewards, tiers, campaigns, and analytics
- An **admin console** for running one installation that can serve one or many businesses

**For business owners:** Launch a professional loyalty program in minutes. No technical skills required.

**For freelancers and agencies:** Offer loyalty programs as a service to your clients. One installation per client, or one installation serving multiple businesses.

**For associations and networks:** Provide loyalty infrastructure to member businesses through a single deployment with complete data isolation.

### The Core Truth

Customers don’t “scan to earn points.” **Staff credit points/stamps at checkout.** Customers present a QR code (or are found by email/name). This makes the system auditable, abuse-resistant, and realistic for real stores.

---

## Key Features

- **Points-based loyalty cards** — Flexible earning rules, welcome bonuses, expiration policies
- **Digital stamp cards** — "Buy 10, get 1 free" that customers can't lose
- **Voucher campaigns** — Single codes or batch-generate thousands for marketing
- **Membership tiers** — Automatic progression with points multipliers
- **Member referrals** — Dual rewards for referrer and referee
- **Email campaigns** — Segment by points, stamps, tiers, or language
- **AI writing assistant (optional)** — Improve copy via an OpenAI-compatible API (requires configuration)
- **Passwordless member login** — Secure 6-digit codes via email (OTP)
- **QR code workflows** — Fast, frictionless point-of-sale interactions
- **Multi-tenant architecture** — Complete partner isolation with shared infrastructure
- **The Shared Wallet** — One customer account works at every business on your installation
- **White-label ready** — Your branding, your domain, your platform
- **One-click updates (where supported)** — New versions delivered to your dashboard (requires `exec()` enabled)
- **Offline access (PWA)** — Members can show cached QR codes offline after first visit (staff still need internet to record transactions)

### Optional / Alpha Features

- **Shopify integration (alpha, optional)** — Online point earning and storefront widget (availability depends on installation configuration)

---

## Technology Stack

### Backend
- **PHP** 8.4.0 or higher
- **Laravel** 13.x
- **Database:** SQLite 3.26+, MySQL 5.7+, MariaDB 10.3+, or PostgreSQL 10.0+

### Frontend
- **Tailwind CSS** 4.x
- **Alpine.js** 3.x
- **Flowbite** component library

### Build Tools
- **Vite** for asset bundling

For complete dependency lists, see `composer.json` (PHP) and `package.json` (JavaScript).

---

## Requirements

### Server Requirements
- PHP 8.4.0 or higher
- Apache or Nginx web server
- SQLite 3.26+, MySQL 5.7+, MariaDB 10.3+, or PostgreSQL 10.0+
- HTTPS certificate (required for production)

### Required PHP Extensions

Most hosting providers include these by default. The installer will verify their presence:

- Bcmath (`ext-bcmath`)
- Ctype (`ext-ctype`)
- cURL (`ext-curl`)
- DOM (`ext-dom`)
- Exif (`ext-exif`)
- Fileinfo (`ext-fileinfo`)
- Filter (`ext-filter`)
- GD (`ext-gd`)
- Hash (`ext-hash`)
- Iconv (`ext-iconv`)
- Intl (`ext-intl`)
- JSON (`ext-json`)
- Libxml (`ext-libxml`)
- Mbstring (`ext-mbstring`)
- OpenSSL (`ext-openssl`)
- PCRE (`ext-pcre`)
- PDO (`ext-pdo`)
- PDO SQLite (`ext-pdo_sqlite`)
- Session (`ext-session`)
- Tokenizer (`ext-tokenizer`)
- XML (`ext-xml`)
- Zlib (`ext-zlib`)

### Shared Hosting Note

If using shared hosting, ensure `proc_open` and `proc_close` PHP functions are enabled. These are required for database migrations and are safe to use.

### One-Click Updates

One-click updates require the `exec()` PHP function. Many shared hosting providers disable this function. If `exec()` is unavailable, use manual updates by downloading from CodeCanyon — your installation works perfectly either way.

---

## Installation

### Production Installation

1. **Upload files** — Extract the contents of `public_html` from the zip file to your website's root directory.

2. **Navigate to your domain** — Open your browser and go to the URL where you uploaded the files.

3. **Complete the installer** — Follow the on-screen instructions to configure your database and create your admin account.

4. **Log in** — Access the admin panel at `yourdomain.com/en-us/admin` to start creating partners and loyalty programs.

**Important:** Install at the root of a domain or subdomain (e.g., `loyalty.yourdomain.com`), not in a subdirectory (e.g., `yourdomain.com/loyalty`).

### Local Development

For local environments, use Laravel's built-in server:

```sh
php artisan serve
```

Then access `http://localhost:8000` in your browser.

---

## Updating

### Check Your Version

Sign in as admin at `yourdomain.com/en-us/admin`. Your current version is displayed on the dashboard.

### One-Click Updates

1. Go to **License & Updates** in the admin panel
2. Validate your license
3. Click to download and install available updates

Updates include automatic database migrations — no manual steps required.

---

## Troubleshooting

### Check the Logs

Error details are written to `storage/logs/laravel.log`. This is your first resource for diagnosing issues.

### 500 Error After Update

If you encounter a 500 error after updating:

1. Check `storage/logs/laravel.log` for specific error messages.

2. Verify that `proc_open` and `proc_close` are not in your PHP `disable_functions` directive.

3. Clear the application cache:
   ```sh
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

4. Ensure file permissions allow the web server to write to `storage/` and `bootstrap/cache/`.

---

## Resources

- [Documentation](https://rewardloyalty.co/docs) — Complete guides for every feature
- [Live Demo](https://rewardloyalty.co/demo) — Test all four user portals
- [System Requirements](https://rewardloyalty.co/docs/getting-started/requirements)
- [Changelog](https://rewardloyalty.co/docs/changelog/changelog)

---

## Support

For questions or issues, visit our [Product Page](https://rewardloyalty.co) or contact support through your purchase platform.

---

## License

Reward Loyalty — Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.
