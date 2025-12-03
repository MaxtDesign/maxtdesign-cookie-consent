---
title: MaxtDesign Cookie Consent - Google Consent Mode v2 Docs
description: Documentation entry point for the MaxtDesign Cookie Consent - Google Consent Mode v2 plugin
keywords: [consent, gdpr, ccpa, wordpress, gcm]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-11-10
version: 1.6.0
---

## Overview

Initial documentation scaffolding for MaxtDesign Cookie Consent - Google Consent Mode v2. Details will be expanded in subsequent phases.


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


