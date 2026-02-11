---
title: MaxtDesign Cookie Consent - Google Consent Mode v2 Docs
description: Documentation entry point for the MaxtDesign Cookie Consent - Google Consent Mode v2 plugin
keywords: [consent, gdpr, ccpa, wordpress, gcm]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-11-10
version: 1.7.1
---

## Overview

Lightweight consent management with proper Google Consent Mode v2 implementation.

## Recent Critical Fix (v1.7.1)

**Fixed:** Google Consent Mode v2 timing issue where GTM/GA4 scripts could execute before consent state was set, resulting in tracking even when users declined consent.

**How it works now:**
1. Plugin injects `gtag('consent', 'default', {...})` in `<head>` with all consent denied (before GTM loads)
2. GTM/GA4 loads but respects the denied state
3. User makes consent choice
4. Plugin updates consent state via `gtag('consent', 'update', {...})`
5. Tracking only fires after explicit consent

**Admin Control:**
The default consent injection can be disabled in Settings > Cookie Consent > Advanced Settings if it conflicts with your setup. However, disabling it may cause tracking to fire before user grants consent.

**For users upgrading from 1.7.0 or earlier:** No action required. The fix automatically applies to all GTM/GA4 installations and is enabled by default.


## Popup Display Fix (v1.7.2)

**Fixed:** Race condition where popup JavaScript initialized before popup HTML was rendered, causing inconsistent display (particularly in Chrome).

**Technical:** Changed initialization from `DOMContentLoaded` to window `load` event to ensure popup HTML exists before JavaScript interaction.

**For users upgrading:** No action required. Popup will now display consistently on all pages.


## Uninstall and Data Removal

When you delete the plugin from WordPress (Plugins > Delete), all plugin data is removed from the database for a clean uninstall.

- Deleted options: `mdcc_settings` (array), `mdcc_version` (string)
- Deleted transients: `mdcc_cache` (if present)
- Multisite: Cleanup runs on all sites in the network
- Not stored server-side: No custom tables, no cron jobs, no user/post/term/comment meta
- Client-side: User consent data is stored in browser localStorage and is unaffected by uninstall

Reinstalling the plugin starts with default settings.


## Validation & Release Docs

- Submission Guide: `docs/submission-guide.md`
- Validation Report & Checklists: `docs/validation-report.md`
- Post-Launch Monitoring Plan: `docs/monitoring-plan.md`
- Assets Checklist: `docs/assets/assets-checklist.md`
- Screenshots Plan: `docs/assets/screenshots.md`


## Website & Documentation To-Do

The following website pages and documentation links need to be created before they can be added back to the plugin readme.txt:

- [ ] **Elementor Integration Documentation:** `https://maxtdesign.com/lean-consent/elementor-integration`
  - Documentation page explaining how to integrate custom Elementor popups with the plugin
  - Should include step-by-step instructions, button action setup, and examples

- [ ] **Main Documentation Hub:** `https://maxtdesign.com/lean-consent/docs`
  - Central documentation page for the plugin
  - Should include user guides, developer documentation, FAQ, and links to other resources

- [ ] **Pro Version Waitlist:** `https://maxtdesign.com/lean-consent/pro`
  - Landing page for the Pro version waitlist
  - Should highlight Pro features, pricing (when available), and signup form

**Note:** These links have been temporarily removed from `readme.txt` until the pages are created. Once completed, they should be added back to the appropriate sections in the readme.


