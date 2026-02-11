---
title: Shortcodes
description: Display consent status and provide a manage interface anywhere via shortcodes
keywords: [shortcodes, consent, status, manage]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-10-30
version: 1.7.0
---

## Overview

Two shortcodes let you display the current consent status and offer a full manage interface anywhere (pages, posts, widgets, builders):

- `mdcc_consent_status` — compact status chips (Analytics, Ads) that update in real time
- `mdcc_manage_consent` — buttons to Accept All, Analytics Only, Decline All, plus Reset Preferences and live status

Both shortcodes update instantly when consent changes (`mdlc:changed` event) and integrate with the JavaScript API (`window.mdlcConsent`).

## Requirements

- Plugin active (v1.7.0)
- Frontend runtime enqueued automatically (`assets/js/consent-runtime.js`)

## Shortcodes

### 1) Consent Status

Usage:

```text
[mdcc_consent_status]
```

Parameters:

- `show_analytics` (yes/no, default: yes)
- `show_ads` (yes/no, default: yes)

Examples:

```text
[mdcc_consent_status show_ads="no"]
```

### 2) Manage Consent

Usage:

```text
[mdcc_manage_consent]
```

Parameters:

- `title` (string, default: "Your Privacy Choices")
- `description` (string, default: "Choose how we use cookies and similar technologies.")
- `show_status` (yes/no, default: yes)

Examples:

```text
[mdcc_manage_consent title="Cookie Settings" show_status="no"]
```

## Real-time Updates

- Status chips reflect the current state returned by `window.mdlcConsent.current()`
- UI updates on the `mdlc:changed` event fired by the runtime after any action

## Actions Available

- Accept All — `mdlcConsent.acceptAll()`
- Analytics Only — `mdlcConsent.acceptAnalyticsOnly()`
- Decline All — `mdlcConsent.declineAll()`
- Reset Preferences — `mdlcConsent.reset()` (shows a confirmation dialog)

## Accessibility

- Status containers use `role="status"` and `aria-live="polite"`
- Buttons are keyboard-focusable with visible focus rings
- All interactive controls have descriptive ARIA labels

## Styling

- Lightweight inline CSS is auto-enqueued only when a shortcode is present
- Uses the `.mdcc-` prefix to avoid theme conflicts

## Troubleshooting

- If the UI doesn’t update, ensure no JavaScript errors from other plugins/themes
- Verify the page actually includes one of the shortcodes


