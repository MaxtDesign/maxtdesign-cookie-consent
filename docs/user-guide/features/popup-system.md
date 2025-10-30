---
title: Standalone Popup System
description: Accessible, responsive consent popup with three style presets and full keyboard support
keywords: [popup, accessibility, wcag, consent]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-10-30
version: 1.6.0
---

# Standalone Popup System

## Overview
Built-in, dependency-free consent popup with Minimal, Modern, and Bold styles; top/bottom/center positions; slide/fade/none animations; full WCAG 2.1 AA accessibility; and seamless integration with `window.mdlcConsent`.

## Use Cases
- **No page builder:** Use without Elementor or other popup plugins.
- **Accessible consent:** Ensure keyboard and screen reader compatibility.
- **Lightweight UI:** <3KB CSS, inline JS, zero dependencies.

## Requirements
- WordPress 5.8+, PHP 7.4+
- Plugin version 1.6.0

## Basic Usage

### Enable Popup
1. In plugin settings, toggle popup on.
2. Choose style: Minimal, Modern, Bold.
3. Choose position: Top, Bottom, Center.
4. Choose animation: Slide, Fade, None.
5. Optionally set primary color and cookie duration (days).

The popup renders automatically if no Elementor popup ID is set and the popup hasn’t been shown recently.

### Buttons and Actions
- Accept All → `mdlcConsent.acceptAll()`
- Analytics Only → `mdlcConsent.acceptAnalyticsOnly()`
- Decline All → `mdlcConsent.declineAll()`

## Configuration Options

| Option | Type | Default | Description |
|-----|---|---|----|
| `popup_enabled` | boolean | `true` | Enables the built-in popup |
| `popup_style` | string | `'minimal'` | `'minimal' | 'modern' | 'bold'` |
| `popup_position` | string | `'bottom'` | `'top' | 'bottom' | 'center'` |
| `popup_animation` | string | `'slide'` | `'slide' | 'fade' | 'none'` |
| `popup_primary_color` | string | `'#0073aa'` | Hex color used for primary button |
| `popup_title` | string | `'Cookie Consent'` | Popup heading text |
| `popup_message` | string | Consent message | Body text |
| `popup_shown_duration` | integer | `7` | Days to suppress after shown/closed |
| `reprompt_on_decline` | boolean | `false` | Re-prompt once per session after decline |
| `elementor_popup_id` | string | `''` | If set, disables built-in popup |

## Accessibility
- `role="dialog"`, `aria-modal="true"`, labeled via `aria-labelledby` and `aria-describedby`.
- ESC closes popup; TAB cycles within popup (focus trap).
- Visible focus styles; high contrast and reduced motion supported.

## Examples

### Check current consent
```javascript
mdlcConsent.current(); // { analytics: false, ads: false }
```

### Grant or decline
```javascript
mdlcConsent.acceptAll();
mdlcConsent.acceptAnalyticsOnly();
mdlcConsent.declineAll();
```

## Troubleshooting
- Popup not showing: ensure `popup_enabled` is true, `elementor_popup_id` is empty, and clear the `mdcc_popup_shown` cookie.
- Styling: `popup_primary_color` controls the primary button color; ensure a valid hex color.
- Accessibility: Confirm ESC closes and focus stays trapped within the popup.

## Related Documentation
- Core Consent Manager

## Changelog
- Added in 1.6.0


