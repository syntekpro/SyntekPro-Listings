# Changelog

All notable changes to this project are documented in this file.

## [1.0.4] - 2026-06-04
- Added branded admin dashboard header with centered plugin logo.
- Added red version badge and light green header background.
- Added custom plugin icon for WordPress admin menu.

## [1.0.3] - 2026-06-04
- Fixed admin memory exhaustion fatal caused by recursive plugin bootstrap call in `SyntekPro_Admin` constructor.
- Refactored core/admin settings wiring to inject `SyntekPro_Settings` dependency without calling `SyntekPro()` during singleton construction.

## [1.0.2] - 2026-06-04
- Fixed fatal error by correcting i18n init callback in core (`load_plugin_textdomain` to `load_textdomain`).
- Removed PHP 8-only union return type from template loader to maintain PHP 7.4 compatibility.

## [1.0.1] - 2026-06-04
- Fixed plugin activation bootstrap by loading `SyntekPro_Activator` before registering activation/deactivation hooks.
- Confirmed plugin activation path works in WordPress plugin API.

## [1.0.0] - 2026-06-04
- Initial public release of SyntekPro Listings.
- Added listing management with multiple listing types:
  - Property (sales, lettings, commercial, student accommodation)
  - Vehicles, jobs, holiday lets, business-for-sale, rental equipment
- Added advanced search features:
  - Filters, location autocomplete, infinite scroll
  - Map search, radial search, draw-a-search
- Added listing detail features:
  - Enquiry and viewing booking forms
  - Printable brochure, window card, and digital display
  - EPC and QR generation
- Added calculators:
  - Mortgage, stamp duty, rental yield, rental affordability
- Added CRM tools:
  - Contacts, tasks, calendar, enquiries, viewings
- Added integrations:
  - Elementor widgets and Divi modules
  - SEO compatibility for Yoast, AIOSEO, and Rank Math
- Added import/export adapters and portal builder.
- Added shortlist and saved searches with alerts.
- Added uninstall routine, readme, and translation template.
