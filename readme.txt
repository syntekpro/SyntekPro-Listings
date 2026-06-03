=== SyntekPro Listings ===
Contributors: syntekpro
Tags: real estate, property listings, estate agent, letting agent, mortgage calculator, stamp duty, map search, CRM, portal export, Elementor, Divi
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive property listings and estate agency plugin — search, maps, CRM, import/export, calculators, and more.
Homepage: https://plugins.syntekpro.com/listings

== Description ==

SyntekPro Listings is the most feature-complete property management plugin for WordPress.

**Listing Types Supported**
* Residential sales & lettings
* Commercial property
* Student accommodation
* Holiday lets
* Vehicles, jobs, business-for-sale, rental equipment

**Core Features**
* Property archive with advanced filtering (price, beds, bathrooms, type, status, location, features)
* Radial (radius) search and Draw-a-search (freehand polygon)
* Interactive map search powered by Google Maps, Mapbox GL JS, or OpenStreetMap / Leaflet
* Location autocomplete
* Infinite scroll or traditional pagination
* Shortlist / save properties (logged-in DB storage + guest session)
* Saved searches with email alerts (daily cron)
* Search results promotional slots
* Send-to-friend

**Property Detail Page**
* Gallery with thumbnails
* EPC chart (SVG, bands A–G)
* QR code (Google Charts)
* Printable brochure, window card, and digital display
* Floorplan support
* Mortgage, stamp duty (SDLT/LBTT/LTT), rental yield, and affordability calculators
* Book a viewing / enquiry form with honeypot spam protection

**CRM**
* Contacts database (add/edit/delete)
* Tasks with due dates
* Calendar (FullCalendar)
* Enquiry and viewing management
* Calendar, appointment, viewing, call, and task event types

**Import**
Supports 19 feed formats: Rightmove BLM v3, Zoopla XML, Alto, Street.co.uk, Reapit, 10Ninety, SME Professional, dezrez, Kyero, agentOS, Juvo, Juxpix, Arthur Online, VaultEA, Kato, Loop, Generic JSON, Generic CSV, Generic XML.

**Export / Portals**
* Export to Rightmove BLM, XML, JSON, CSV
* Public live feed URLs with optional secret key
* Portal builder — configure unlimited portals with per-listing exclusion rules

**AI**
* OpenAI-powered description generation (GPT-4o mini / GPT-4o / GPT-3.5 Turbo)
* Rewrite in tones: professional, friendly, luxury, concise, detailed

**Page Builder Integrations**
* Elementor — Listing Grid, Listing Map, Search Form, Mortgage Calculator, Featured Listing widgets
* Divi — Listings Grid, Listing Map, Search Form, Calculator modules

**SEO**
* Yoast SEO, AIOSEO, and Rank Math compatible
* Automatic JSON-LD (RealEstateListing schema)
* Open Graph meta tags
* XML sitemap entries

**White Label**
* Rename plugin and admin menu
* Replace logo and primary colour

**International**
* 14 currencies (GBP, EUR, USD, AUD, CAD, CHF, SEK, NOK, DKK, JPY, CNY, INR, AED, ZAR)
* 24 countries
* Full translation-ready (text domain: `syntekpro-listings`)

**Customisable Templates**
Override any template by placing it in your theme's `syntekpro-listings/` directory.

== Installation ==

1. Upload the `SyntekPro-Listings` folder to `/wp-content/plugins/`.
2. Activate the plugin via the **Plugins** admin screen.
3. Navigate to **SyntekPro Listings → Settings** to configure API keys and preferences.
4. Use the provided shortcodes or page-builder widgets to add listings to your pages.

== Shortcodes ==

* `[syntekpro_listings]` — Grid of listings
* `[syntekpro_search]` — Search form
* `[syntekpro_map]` — Interactive map
* `[syntekpro_calculator type="mortgage"]` — Calculator
* `[syntekpro_shortlist]` — Saved properties page
* `[syntekpro_saved_searches]` — Saved searches page
* `[syntekpro_epc current="C" potential="B"]` — EPC chart
* `[syntekpro_enquiry_form listing_id="123"]` — Enquiry form
* `[syntekpro_send_to_friend listing_id="123"]` — Send to friend
* `[syntekpro_mortgage_calculator]` — Standalone mortgage calculator
* `[syntekpro_stamp_duty_calculator]` — Stamp duty calculator
* `[syntekpro_rental_yield_calculator]` — Rental yield calculator
* `[syntekpro_rental_affordability_calculator]` — Affordability calculator
* `[syntekpro_map_search]` — Full-page map search

== Frequently Asked Questions ==

= Which map providers are supported? =
Google Maps (requires API key), Mapbox GL JS (requires access token), and OpenStreetMap via Leaflet (no key required).

= Can I use this for non-residential property? =
Yes. The plugin supports commercial, student accommodation, holiday lets, vehicles, jobs, business-for-sale, and rental equipment listings.

= Does it work with Elementor Free? =
Yes, all widgets are available in both Elementor Free and Pro.

= How do I override a template? =
Copy the template file from `wp-content/plugins/SyntekPro-Listings/templates/` to `wp-content/themes/your-theme/syntekpro-listings/` and edit it there.

= Where is import data stored? =
Imported listings are saved as `syntekpro_listing` custom post type entries. Each import run is logged in the `syntekpro_import_log` database table.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade required.
