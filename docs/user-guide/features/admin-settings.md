---
title: Admin Settings
description: Configure popup appearance, content, behavior, and optional Elementor integration
keywords: [settings, admin, color picker, elementor]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2025-10-30
version: 1.6.0
---

# Admin Settings

## Overview
Configure the consent popup using a clean settings page at Settings > Lean Consent. Options cover appearance, content, behavior, and Elementor integration.

## Sections
- **Popup Appearance**: Enable, style preset, position, primary color, animation
- **Popup Content**: Title and message
- **Behavior Settings**: Cookie duration (days), re-prompt on decline
- **Elementor Integration**: Optional popup ID to use an Elementor popup instead of the built-in one

## Basic Usage
1. Go to Settings > Lean Consent.
2. Adjust appearance (style, position, color, animation).
3. Set title and message.
4. Choose cookie duration (1–365 days) and re-prompt behavior.
5. Optionally enter an Elementor Popup ID to disable the built-in popup.
6. Click Save Settings.

## Configuration Options

| Option | Type | Default | Description |
|-----|---|---|----|
| `popup_enabled` | boolean | `true` | Enable built-in popup |
| `popup_style` | string | `'minimal'` | One of `minimal`, `modern`, `bold` |
| `popup_position` | string | `'bottom'` | One of `top`, `bottom`, `center` |
| `popup_primary_color` | string | `'#0073aa'` | Hex color for primary button |
| `popup_animation` | string | `'slide'` | One of `slide`, `fade`, `none` |
| `popup_title` | string | `'Cookie Consent'` | Popup heading |
| `popup_message` | string | See default | Popup body text |
| `popup_shown_duration` | integer | `7` | Days before re-showing after close (1–365) |
| `reprompt_on_decline` | boolean | `false` | Re-prompt once per session after decline |
| `elementor_popup_id` | integer/string | `''` | If set, built-in popup is disabled |

## Shortcodes
- `[mdcc_consent_status]` – Displays current consent status chips.
- `[mdcc_manage_consent]` – Renders consent management buttons.

## Troubleshooting
- **Popup not showing**: Ensure `popup_enabled` is true, `elementor_popup_id` is empty, and clear the `mdcc_popup_shown` cookie.
- **Color not applied**: Provide a valid hex color (e.g., `#0073aa`).
- **Cookie duration ignored**: Values outside 1–365 are reset to defaults.

## Related Documentation
- Standalone Popup System

## Changelog
- Added in 1.6.0


