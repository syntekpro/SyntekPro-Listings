# SyntekPro Listings v1.0.3

Release date: 2026-06-04

## Critical Fix
- Fixed admin memory exhaustion fatal error:
  - `Allowed memory size ... exhausted ... wp-includes/functions.php line 655`
- Root cause was recursive plugin bootstrap in admin context.

## Technical Changes
- Removed recursive `SyntekPro()` call from `SyntekPro_Admin` constructor.
- Injected settings dependency from core into admin class.
- Preserved existing behavior for settings registration and rendering.

## Download
- SyntekPro-Listings-v1.0.3.zip
