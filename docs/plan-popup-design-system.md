# Plan: popup design system (design settings + theme template override)

Written 2026-09-28. Status: **ACCEPTED by the operator 2026-09-28. Decisions D1a and D2 (link only) are locked. Build not started.**
Target release: folds into the unreleased **1.11.0** (wordpress.org serves 1.10.0).
Branch: `feat/popup-design-system`, stacked on `feat/country-gpc-consent` (`f91fd87`).

## 1. Goal

Let a site design its own consent popup without Elementor, in two ways:

- **A. Design settings** for site owners. No code.
- **B. Theme template override** for developers. Full control of the markup.

The layout the operator asked for becomes the first saved combination of A:
60% wide and centered on desktop, full width on tablet and mobile, a "Manage
options" link to the site's cookie settings page, and an "Accept all" button.

## 2. Two conditions from the operator

1. **The current plugin must not change.** Every new setting defaults to
   today's value. A site that upgrades and touches nothing renders the same
   popup. Phase 0 captures the evidence to prove this, and phase 4 compares.
2. **Setup must be clearly explained.** The admin screen carries the steps
   (section 6). The readme and user guide repeat them.

## 3. Floors and facts (from this session's hook output and source)

| Item | Value | Source |
|---|---|---|
| WordPress current | 7.1.2 | `plugin-versions.php`, cached 2026-09-28 |
| Plugin floors | WordPress 5.8, PHP 7.4 | plugin header. Legacy floor, so all new code must run on PHP 7.4 |
| `locate_template()`, `load_template()` with `$args` | present; `$args` since WordPress 5.5 | `wp-includes/template.php` lines 722 and 782 |
| `wp_dropdown_pages()` | present | `wp-includes/post-template.php` line 1201 |
| Prefixes | classes `MDCC_`, options and hooks `mdcc_`, no namespace. LOCKED | naming registry row 62 |
| Popup markup | server-rendered once, shared by every visitor through the page cache | `class-popup-system.php` |
| Popup CSS and JS | fetched only for visitors who need the dialog, by a 481 B inline loader | `popup-loader.js` |

## 4. Byte budget

This is the tightest constraint in the plan.

| Budget | Cap | Used today | Headroom |
|---|---|---|---|
| Core (`popup.min.css` + `consent-runtime.min.js`) | 10,240 B | 10,101 B | **139 B** |
| Full dialog (all four assets) | 14,336 B | 13,797 B | 539 B |
| Inline loader | under 1,024 B | 481 B | 543 B |

Rules for this work:

- **The caps do not move.** If the work cannot fit, it stops and comes back to
  the operator with numbers.
- CSS additions are paid for by trims in the same file. Candidates: the
  duplicated `:focus` and `:focus-visible` outline rules, and the inline
  primary-colour block, which moves into the stylesheet as a variable.
- Design values print as inline CSS variables, and **only values that differ
  from the default are printed**. A default site prints none.
- The inline variable block gets its own size assertion in
  `tools/validate-size.js`: under 512 B with every field set.
- Today's inline primary-colour block is not counted by any check. Phase 0
  measures it so the before and after are comparable.

## 5. Part A: design settings

All fields live in the existing `mdcc_settings` option. No new option, no new
table. `uninstall.php` already removes `mdcc_settings`, so it needs no change.

| Setting key | Control | Default (today's behaviour) | Output |
|---|---|---|---|
| `popup_buttons` | select: Standard, Compact | `standard` (Accept All, Analytics Only, Decline All) | class on the popup |
| `manage_page_id` | page picker | `0` (none) | link `href` |
| `popup_desktop_width` | select: 100, 80, 60, 50 percent | `100` | `--mdcc-w` |
| `popup_bg_color` | colour | empty | `--mdcc-bg` |
| `popup_text_color` | colour | empty | `--mdcc-fg` |
| `popup_button_text_color` | colour | empty | `--mdcc-btn-fg` |
| `popup_radius` | number, 0 to 24 px | empty (the style preset decides) | `--mdcc-r` |
| `popup_inherit_font` | checkbox | off | `--mdcc-font: inherit` |
| `label_accept`, `label_manage`, `label_decline`, `label_analytics` | text | empty (the translated default string is used) | button text |

The existing `popup_primary_color` keeps working and becomes `--mdcc-primary`.

Behaviour:

- **Desktop width** applies at 1025 px and wider, for the Top and Bottom
  positions. Below 1025 px the popup stays full width as it is today. The
  Center position keeps its existing modal size.
- **Compact** needs a cookie settings page. If `manage_page_id` is empty, or
  the page is not published, the popup renders **Standard**, and the settings
  screen shows a warning. A visitor is never left without choices.
- **Inherit font** fixes a gap found on 2026-08-05: the buttons render in the
  browser default font while the title picks up the theme's heading font.

### Who sees which controls in Compact

The markup is cached and identical for everyone, so the per-visitor part runs
in `popup.js` using the existing `mdccConsent.bannerMode()`.

| Visitor | Controls shown |
|---|---|
| `optin` (nothing is tracked until they accept) | Manage options, Decline, Accept all |
| `optout` (tracking on, may opt out) | Manage options link, labelled with the opt-out wording, and Got it |
| `none` | no popup at all, as today |

**This needs the operator's attention before build. See decision D1.**

## 6. Setup text shown in the admin

Under the Buttons field:

> **Compact** shows a "Manage options" link and an "Accept all" button.
> To use it:
> 1. Create a page, for example "Cookie settings".
> 2. Add the shortcode `[mdcc_manage_consent]` to that page and publish it.
> 3. Select that page under **Cookie settings page** below.
> 4. Set **Buttons** to Compact and save.
>
> Visitors who must give consent before tracking also see a Decline button.
> If no page is selected, the popup keeps the standard three buttons.

## 7. Part B: theme template override

- The popup markup moves from `render_popup()` into `templates/popup.php`
  inside the plugin. Output stays byte-identical for a default site.
- A theme overrides it by adding
  `yourtheme/maxtdesign-cookie-consent/popup.php`. Child themes are checked
  first, because `locate_template()` does that.
- New filter `mdcc_popup_template` receives the resolved path, for sites that
  keep templates elsewhere.
- The template receives one array: the settings, the resolved labels, the
  manage URL and the CSS classes. The template does its own escaping.
- **Contract** the override must keep, documented at the top of the file:
  the root `.mdcc-popup` element, `#mdcc-popup-title`, `#mdcc-popup-message`,
  and the `data-mdcc-action` values `accept-all`, `decline-all` and
  `analytics-only`. `popup.js` finds elements by these and nothing else.
- The template carries a `@version`. The plugin's settings screen shows a
  notice when a theme override is older than the bundled template. The check
  runs on the plugin's settings screen only, never on the frontend.
- The existing `mdcc_popup_before_actions` action stays where it is.

## 8. Security surface

| Input | Handling |
|---|---|
| Colours | `sanitize_hex_color()` on save, and again before output |
| Width | checked against the four allowed values |
| Radius | `absint()`, clamped 0 to 24 |
| `popup_buttons` | checked against the two allowed values |
| `manage_page_id` | `absint()`, must be a published page, else 0 |
| Labels | `sanitize_text_field()` on save, `esc_html()` and `esc_attr()` on output |
| Manage URL | `get_permalink()`, then `esc_url()` |

Saving goes through the existing Settings API form, which already carries the
nonce and the `manage_options` capability check. There are no new REST routes,
no new AJAX handlers and no outbound HTTP. A theme template is code the site
owner installed, the same trust level as the theme itself.

## 9. Phases

Each phase is one commit and leaves the tests green.

| # | Phase | Done when |
|---|---|---|
| 0 | **Baseline.** Record rendered popup HTML, inline CSS and computed styles for the three presets and three positions at 1440, 1024, 768 and 375 px wide. Measure today's inline block. | Baseline files saved under `tests/fixtures/` and numbers written into `docs/STATE.md` |
| 1 | **Packaging fix** (see risk R1). `tools/prepare-svn.sh` copies `popup-loader.js`, `popup-loader.min.js` and the new `templates/` folder. `bin/build-zip.php` allow-list gains `templates/*.php`. | The built zip contains the loader and the template, checked by extracting it |
| 2 | **Template extraction (B).** Markup moves to `templates/popup.php`, with the override lookup and the filter. | Rendered HTML for a default site is byte-identical to the phase 0 baseline |
| 3 | **Design variables (A, part 1).** Stylesheet reads variables with today's values as fallbacks. Inline block prints only non-default values. New fields: width, colours, radius, font. | Core budget passes with the cap unchanged. Default site computed styles match baseline at all four widths |
| 4 | **Compact layout (A, part 2).** Buttons field, page picker, labels, per-visitor controls, setup text, fallback to Standard. | The three visitor modes each show the controls in section 5, driven by the existing timezone harness, with 0 console errors |
| 5 | **Docs and gates.** readme changelog entry under 1.11.0, user guide, `docs/JAVASCRIPT-API.md` template contract, `docs/STATE.md`. PHPStan level 8, Plugin Check on the staged trunk under the real slug. | All checks recorded with numbers and dates in `docs/STATE.md` |

Release is not part of this plan. Shipping 1.11.0 to wordpress.org is a
separate step that needs `release-gate.php` to pass and the operator's
explicit go.

## 10. Quality standard record (planned evidence)

| Principle | How it will be shown |
|---|---|
| Performance and footprint | `validate-size.js` with unchanged caps, the new inline assertion, request count before and after |
| Security | section 8, traced input to output in review, PHPStan level 8 |
| Compliance | decision D1 recorded. Privacy policy text reviewed against the new controls. No claim of legal compliance is made by this plan |
| Accessibility | keyboard order and focus trap re-tested with the manage link added; contrast checked for operator-chosen colours is the site owner's responsibility and the admin text says so |

## 11. Risks

- **R1. The 1.11.0 tree has a packaging gap.** `tools/prepare-svn.sh` does not
  copy `popup-loader.js` or `popup-loader.min.js`. The popup is loaded by that
  file. A wordpress.org release staged with this script would ship a plugin
  whose popup never appears. MaxtOffroad is not affected, because its artifact
  was built another way. `bin/build-zip.php` is not affected, because it
  matches `assets/js/*.js`. Phase 1 fixes it. **This blocks any wordpress.org
  release of 1.11.0, with or without this plan.**
- **R2. Core budget has 139 B of headroom.** The variable work may not fit
  even with trims. If so the work stops at phase 3 and returns with numbers.
- **R3. Theme overrides can break the popup.** An override that drops a
  required element breaks the buttons. The contract comment and the outdated
  template notice reduce this. They do not remove it.
- **R4. Operator-chosen colours can fail contrast.** The plugin will not block
  a colour choice. The admin text warns.
- **R5. `docs/STATE.md` is still mostly a seed.** Identity still says 1.10.0
  and several sections are unfilled. Phase 0 backfills it.

## 12. Operator decisions

- **D1. Compact never shows exactly two controls to a visitor who must opt
  in.** On MaxtOffroad the country endpoint returns `none` or `optin`. United
  States visitors see no popup. Everyone who does see the popup is `optin`,
  so they will see Manage options, Decline and Accept all. The two-control
  version appears only for `optout` visitors, which MaxtOffroad does not
  currently produce. Options:
  - **D1a (planned):** keep Decline for `optin` visitors, as agreed.
  - **D1b:** show exactly two controls to everyone, with an admin caution
    that this may not meet EEA and UK rules. The operator owns that call.
- **D2.** For `optout` visitors in Compact, the Manage options link carries
  the opt-out wording and leads to the cookie settings page, and there is no
  separate opt-out button on the popup. Confirm this is acceptable, or keep
  the opt-out button on the popup for those visitors.
- **D3.** Accept this plan, or change it.

### Decisions recorded 2026-09-28 (operator)

- **D1: D1a.** Decline stays for `optin` visitors in Compact.
- **D2: link only.** For `optout` visitors in Compact, the Manage options
  link carries the opt-out wording. No separate opt-out button on the popup.
- **D3: accepted** as written.
