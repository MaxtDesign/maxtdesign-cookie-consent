---
title: Popup Design
description: Design settings, the Compact button layout and the theme template override
keywords: [popup, design, compact, template, css variables]
category: user-guide
audience: user
difficulty: beginner
last_updated: 2026-09-28
version: 1.11.0
---

# Popup Design

## Overview
Design your consent popup without a page builder. There are two ways.

- **Design settings** for site owners. No code. Settings > Cookie Consent > Popup Design.
- **Theme template override** for developers who want their own markup.

Every setting is optional. A site that changes nothing shows the same popup as before 1.11.0.

## Design settings

| Setting | Values | Default | What it does |
|---|---|---|---|
| Buttons | Standard, Compact | Standard | Standard shows Accept All, Analytics Only, Decline All. Compact is described below |
| Cookie settings page | a published page | None | The page Compact links to |
| Button Labels | text | empty | Your own wording. Empty uses the default in the site language |
| Desktop Width | 100, 80, 60, 50 percent | 100 | Width of the Top and Bottom banner on screens 1025 pixels and wider. Centered |
| Background Color | hex color | empty | Popup background |
| Text Color | hex color | empty | Title, message, close and decline text |
| Primary Button Text Color | hex color | empty | Text on the Accept button |
| Corner Radius | 0 to 24 pixels | empty | Corners of the popup and its buttons |
| Button Font | on, off | off | On: buttons use the theme font. Off: buttons use the browser font |

Empty means the style preset decides.

Notes:

- On tablets and phones the banner is always full width.
- The Center Modal keeps its own size. Desktop Width does not apply to it.
- The plugin does not check your colors for contrast. Make sure the text stays readable against the background you choose.

## Set up the Compact layout

Compact shows a "Manage options" link and an "Accept all" button.

1. Create a page, for example "Cookie settings".
2. Add the shortcode `[mdcc_manage_consent]` to that page and publish it.
3. Select that page under **Cookie settings page**.
4. Set **Buttons** to Compact and save.

If no published page is selected, the popup keeps the standard three buttons. The settings screen shows a warning in that case. The same happens when the page is later unpublished or moved to the trash. Compact returns when the page is published again.

### What each visitor sees

| Visitor | Controls |
|---|---|
| Must give consent before tracking (opt-in) | Manage options, Decline, Accept all |
| Tracked by default, may opt out (opt-out regions) | a link with the wording "Do Not Sell or Share My Personal Information" that leads to the cookie settings page, and Got it |
| No banner region | no popup |

Which group a visitor is in comes from the Consent Model setting. With the default model, Opt-in everywhere, every visitor is in the first group.

## The layout the feature was built for

60 percent wide and centered on desktop, full width on tablet and phone, a "Manage options" link and an "Accept all" button:

1. Follow the four Compact steps above.
2. Set **Position** to Bottom Banner or Top Banner.
3. Set **Desktop Width** to 60%.
4. Optional: type `Accept all` under Button Labels if you want that exact capitalization.

## Theme template override

1. Copy `templates/popup.php` from the plugin folder.
2. Paste it into your theme as `maxtdesign-cookie-consent/popup.php`. A child theme is checked before its parent.
3. Edit your copy.

Keep the items listed in the comment at the top of the file. The buttons work through them. The full contract is in `docs/JAVASCRIPT-API.md`, section Popup Template Contract.

The plugin settings screen tells you when an override is in use. It warns when your copy is older than the template in the plugin. Compare the two files and update yours.

Sites that keep templates outside the theme folder can return a path from the `mdcc_popup_template` filter:

```php
add_filter('mdcc_popup_template', function ($template) {
    return WP_CONTENT_DIR . '/my-templates/consent-popup.php';
});
```

A path that cannot be read is ignored and the plugin's own template is used.

## Style from your theme with CSS variables

```css
.mdcc-popup {
    --mdcc-primary: #c8102e;
    --mdcc-hover: #a50d26;
    --mdcc-bg: #111111;
    --mdcc-fg: #ffffff;
    --mdcc-r: 8px;
}
```

Variables: `--mdcc-primary`, `--mdcc-hover`, `--mdcc-btn-fg`, `--mdcc-bg`, `--mdcc-fg`, `--mdcc-r`, `--mdcc-w`. Values saved in the design settings win over a theme rule with the same selector only when they print later in the page, so set one or the other, not both.

## Multilingual sites

Return the translated page from the `mdcc_manage_url` filter:

```php
add_filter('mdcc_manage_url', function ($url) {
    return function_exists('pll_get_post') ? get_permalink(pll_get_post(42)) : $url;
});
```

Return an empty string to make the popup fall back to the standard buttons.

## Troubleshooting
- Compact is selected but three buttons show: no published page is selected under Cookie settings page.
- The custom width does not show: it applies from 1025 pixels, and only to the Top and Bottom positions.
- The buttons stopped working after a template override: your copy dropped one of the required items. Compare it with `templates/popup.php`.
- A cached page still shows the old popup: clear the page cache after you save design settings.

## Related Documentation
- Standalone Popup System
- Shortcodes

## Changelog
- Added in 1.11.0
