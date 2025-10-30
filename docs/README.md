---
title: Cookie Consent - Google Consent Mode v2 Docs
description: Documentation entry point for Cookie Consent - Google Consent Mode v2 plugin
keywords: [consent, gdpr, ccpa, wordpress, gcm]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-10-30
version: 1.6.0
---

## Overview

Initial documentation scaffolding for Cookie Consent - Google Consent Mode v2. Details will be expanded in subsequent phases.


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


