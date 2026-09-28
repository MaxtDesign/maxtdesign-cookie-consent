# Build report: popup design system

Written 2026-09-28 by session "MaxtDesign - Cookie Consent 2". Plan:
`plan-popup-design-system.md`. Branch `feat/popup-design-system`, stacked on
`feat/country-gpc-consent` (`f91fd87`). Version 1.11.0, unreleased. Nothing
was released, merged or deployed.

All checks below ran on 2026-09-28 in a git worktree on Windows 11, PHP
8.2.12, Node 24.15.0, Chrome 152.0.7977.85.

## Commits

| Phase | Commit | What |
|---|---|---|
| 0 | `fda69c0` | baseline harness and fixtures, STATE.md backfill |
| 1 | `052104e` | packaging fix |
| 2 | `6b52459` | template extraction and theme override |
| 3 | `6ac14d9` | design settings as CSS variables |
| 4 | `0b27d08` | Compact layout |
| 5 | the commit that adds this file | docs, review fixes, gates |

## Budgets. Caps did not move

| Budget | Cap | Before (`f91fd87`) | After |
|---|---|---|---|
| Core: `popup.min.css` + `consent-runtime.min.js` | 10,240 B | 10,101 B | 10,168 B, 72 B headroom |
| Full dialog: all four assets | 14,336 B | 13,797 B | 13,942 B, 394 B headroom |
| Inline loader | under 1,024 B | 481 B | 481 B |
| Inline style block, default site | none before | 345 B, uncounted | 0 B |
| Inline style block, every field set | under 512 B (new) | not applicable | 267 B |

## The rule: a site that changes nothing renders the same popup

Evidence is `tests/popup-baseline.cjs` against the fixtures recorded from
`f91fd87` in phase 0: 14 cases, the three style presets times the three
positions at 1440, 1024, 768 and 375 px, plus fresh install, privacy link,
custom colour, no animation and the regional model.

- Markup: byte-identical in all 14 cases.
- Computed styles and boxes: identical in 41 of 42 case and width pairs.
- The 42nd pair is the custom primary colour. It changed on purpose, flag F1.
- The inline style went from 345 B to 0 B on a default site.

What the baseline does not cover: hover and focus states (flag F7), a theme
stylesheet, and a render by WordPress itself.

## Flags for the operator

- **F1. A custom Primary Color was not applied in the 1.11.0 candidate. It is
  again.** The candidate prints the inline colour block in the head, and the
  loader adds `popup.min.css` after it. Both rules have the same specificity,
  so the stylesheet won. In 1.10.x the stylesheet came first. The order was
  confirmed on `razorback2` by one read-only page fetch: the inline block is
  in the head and no stylesheet link is in the HTML. That site uses the
  default colour, so it shows no visible effect. Phase 3 moved the colour to
  a CSS variable, which does not depend on order.
- **F7. Two visible changes paid for the variables.** The Close button no
  longer draws an outline on mouse hover. Buttons focused by a mouse click no
  longer draw the 2 px outline. Keyboard focus keeps the 3 px
  `:focus-visible` outline. The plan named these rules as trim candidates.
  Browsers without `:focus-visible` use their own focus ring.
- **F8. The button font setting is not a CSS variable.** The plan said
  `--mdcc-font: inherit`. `inherit` is a CSS-wide keyword, so the custom
  property inherits itself and the fallback wins. Measured: buttons stayed in
  Arial. The setting prints
  `.mdcc-popup .mdcc-popup__button{font-family:inherit}` in the inline block.
  Stylesheet cost 0 B. `--mdcc-hover` was added for the hover colour of a
  custom primary colour. The plan did not list it.
- **F9. Phase 4 choices the plan left open.**
  (a) In Compact the Decline control uses the secondary button style, not the
  underlined text style it has in Standard, so refusing is as visible as
  accepting.
  (b) The address of the cookie settings page is resolved when settings are
  saved and when that page changes. It is stored relative to the home URL.
  Rendering adds no database query, and the link survives a move from staging
  to production.
  (c) New filter `mdcc_manage_url` for multilingual sites.
  (d) Compact's CSS prints in the inline block. The stylesheet grew 0 B.
- **F5. Trailing spaces in `templates/popup.php` are load-bearing.** They keep
  the output byte-identical. An editor that strips trailing whitespace changes
  the markup by 163 B. The baseline test catches it.
- **F6. Not run on PHP 7.4 or WordPress 5.8.** Checked by inspection only.
- **F11. The default hover colour follows 1.10.x, not the candidate.** The
  primary button hovers to `#0073aadd`, the value wordpress.org sites have
  today. The 1.11.0 candidate on MaxtOffroad hovers to `#005a87`, a side
  effect of F1. MaxtOffroad will see the hover colour change when it takes
  this build.
- **F12. In Compact, an opt-out visitor opts out in two steps.** The link
  with the opt-out wording leads to the cookie settings page. It records
  nothing by itself. This is decision D2 as locked. The security audit asked
  that the operator confirm it is intended.
- **F10. Stale documents, not touched.** `SVN-FINAL-CHECKLIST.md`,
  `SVN-UPLOAD-FILE-LIST.md` and `TEST-BEFORE-SVN.md` name a
  `tools/prepare-svn.ps1` that does not exist and a 21 file count. The user
  guide pages other than `popup-design.md` are dated 1.7.0 and use the old
  `mdlcConsent` name. `languages/maxtdesign-cookie-consent.pot` was not
  regenerated, because wp-cli is not installed. translate.wordpress.org
  reads strings from source.

## Quality standard record

| Principle | Result | Evidence |
|---|---|---|
| Performance and footprint | PASS for what was measured. Field Core Web Vitals UNVERIFIED | budgets above; no new request; none-mode visitors request no popup assets; rendering adds no database query; the template lookup is up to three file checks per uncached render |
| Security | PASS: 0 Critical, 0 High, 0 Medium. 4 Low, all fixed. See Reviews | input handling in plan section 8, `tests/popup-settings-test.php`, PHPStan level 8 |
| Compliance | PASS for the recorded decisions. No claim of legal compliance is made | D1a and D2 implemented as decided and tested per visitor mode. The privacy policy text the plugin suggests was read against the new controls: it describes the choice storage and the regional notice, and needs no change. No new data is collected. No outbound HTTP |
| Accessibility | PASS for what was tested. Screen reader behaviour UNVERIFIED | keyboard order and focus wrap with the manage link, in opt-in and opt-out; Escape closes; a custom label is also the accessible name; colour contrast is the site owner's choice and the settings screen says so |

## Verification

| Check | Result |
|---|---|
| `node tests/country-consent.cjs` | 221 of 221 |
| `php tests/functional-tests.php` | 30 of 30 |
| `node tests/popup-baseline.cjs` | 71 of 71 |
| `node tests/popup-design.cjs` | 74 of 74 |
| `node tests/popup-visitor-modes.cjs` | 41 of 41, 0 console errors, warnings or exceptions |
| `php tests/popup-settings-test.php` | 60 of 60 |
| `php tests/popup-template-test.php` | 20 of 20 |
| `php -d extension=zip tests/packaging-test.php` | 24 of 24. 24 files in the extracted zip. The staged trunk equals the zip by sha256 |
| `npm run validate` | passes with the numbers under Budgets |
| `npm run phpstan`, level 8 | 0 errors, `templates/` included. The 1.11.0 candidate had 1 |
| `plugin-deliverables.php` | 14 passed, 0 failed |
| Outbound HTTP grep | 0 hits |

Each new check was shown to fail on a broken input before it was trusted:
a 1 px padding change fails 23 of 71 baseline checks, a markup change fails
15 of 29, staging without the loader fails 2 packaging checks, the phase 3
`popup.min.js` fails 4 visitor mode checks, and an inline cap of 100 B fails
the size check.

## Reviews

Two independent reviews of `f91fd87..0b27d08` ran on 2026-09-28. Both were
read-only.

**Security audit.** 0 Critical, 0 High, 0 Medium, 4 Low, 5 Informational.

| Finding | Fix |
|---|---|
| L1. Unpublishing the cookie settings page in wp-admin cleared the page selection for good, because the sanitize callback runs again when the plugin refreshes the address | the selection is kept while the page exists as a page. Only the address decides the layout. Test added with the callback registered |
| L2. `update_option_permalink_structure` fires before WordPress loads the new structure | hook `permalink_structure_changed` |
| L3. Patterns ending in `$` accept one trailing line break | `z` in both patterns, `trim()` first. Test added |
| L4. The template notice printed a full server path for files outside the WordPress folder | path shown relative to the WordPress or content folder, else the file name only |

**Code review.** 0 Block, 6 Fix-before-merge, 9 Nit. Findings 1 and 2 were
L1 and L2 above.

| Finding | Fix |
|---|---|
| 3. `deleted_post` fires while the deleted page is still cached | hook `after_delete_post` |
| 4. In Compact the popup reopened on the cookie settings page, over the consent controls | no popup on that page in Compact. Decided by the page, so it is safe under a page cache. Test added |
| 5. The default hover colour of the primary button was `#0073aadd` in 1.10.x and `#005a87` in the candidate | fallback set to `#0073aadd`. Sites on wordpress.org upgrade from 1.10.0. Flag F11 |
| 6. One sentence was split into two translated strings | one string with a placeholder |
| Nits taken | address resolved once per render; the `mdcc-popup` style handle is registered on every site again; `popup.js` relabels the link when an override has no decline control; `build-zip.php` prints `
`; `prepare-svn.sh` refuses an output path that does not end in `/trunk`; `_x()` for "none"; the template comment says to print every class |
| Nits not taken | the focus trap counts hidden controls. The bundled template is safe because Accept is last. The template comment now says so |

The fixes after the reviews were not reviewed again.

## Not verified

- A render by WordPress with this branch. `plugin-test.local` returned 502.
  The main checkout stays on `feat/country-gpc-consent` because `razorback2`
  serves it.
- The settings screen in a browser: field layout, colour pickers, the page
  picker, the notices.
- Plugin Check on a staged trunk under the real slug.
- `release-gate.php`. It was not run, so no release marker exists.
- PHP 7.4 and WordPress 5.8 at runtime.
- Field Core Web Vitals.
