---
title: Core Consent Manager
description: Google Consent Mode v2 with localStorage-based consent
keywords: [gcm, consent, localStorage]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-10-30
version: 1.6.0
---

# Core Consent Manager

## Overview
Implements Google Consent Mode v2 with privacy-first defaults using localStorage. No server requests, no database reads on frontend.

## Basic Usage

### Public API
```javascript
// Get current state
mdlcConsent.current(); // { analytics: false, ads: false }

// Grant
mdlcConsent.acceptAll();
mdlcConsent.acceptAnalyticsOnly();

// Revoke/Reset
mdlcConsent.declineAll();
mdlcConsent.reset();
```

### Event
Listen for consent changes to update UI:
```javascript
document.addEventListener('mdlc:changed', function(e) {
  console.log('Consent changed', e.detail);
});
```

## Google Consent Mode v2 Signals
The runtime updates the following signals:
- `analytics_storage`
- `ad_storage`
- `ad_user_data`
- `ad_personalization`

## Default State
Denied by default: `{ analytics: false, ads: false }`.

## Configuration
Passed via WordPress localization:
```json
{
  "storageKey": "mdlc_consent",
  "debug": true
}
```

## Troubleshooting
- Ensure `gtag` is present for GCM updates; otherwise the runtime safely no-ops.
- Verify localStorage access is allowed in the browser (private modes may restrict it).

## Changelog
- Added in 1.6.0


