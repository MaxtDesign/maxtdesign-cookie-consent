# Popup baseline fixtures

Recorded 2026-09-28 from the 1.11.0 candidate (`f91fd87`), before any popup
design system code. Written and checked by `tests/popup-baseline.cjs`.

| Path | Content |
|---|---|
| `markup/{case}.html` | popup markup exactly as `render_popup()` prints it |
| `inline-css/{case}.css` | the inline block passed to `wp_add_inline_style()` |
| `summary.json` | settings, byte counts and the `mdccPopupConfig` object per case |
| `computed.json` | computed styles and boxes per case and viewport width |

Cases: the three style presets times the three positions, at 1440, 1024, 768
and 375 px wide. Plus `fresh-install` (no saved option), `privacy-link`,
`custom-primary`, `animation-none` and `model-regional`.

Compare: `node tests/popup-baseline.cjs`. Re-record: add `--update`.
Re-record only when a change to the default rendering is intended, and say so
in the commit message.

## Intended changes since the recording

| Date | Commit phase | What changed | Why |
|---|---|---|---|
| 2026-09-28 | 3 | `inline-css/*.css` went from 345 B to empty, and the byte counts in `summary.json` follow | the inline primary-colour block became a CSS variable that prints only when it differs from the default |
| 2026-09-28 | 3 | `computed.json`, case `custom-primary@1440`: the Accept button is `rgb(200, 16, 46)`, was `rgb(0, 115, 170)` | the saved primary colour was not applied in the 1.11.0 candidate; it is again |

Nothing else changed. The markup of all 14 cases and the computed styles of
the other 41 case and width pairs are as recorded from `f91fd87`.

Limits. The markup comes from the real plugin classes behind WordPress stubs
(`tests/popup-render.php`), not from a WordPress site. The page has no theme
stylesheet. Boxes depend on the fonts installed on the recording machine.
Line endings are compared as LF.
