# STATE: maxtdesign-cookie-consent
Updated: 2026-09-28 by session "MaxtDesign - Cookie Consent 2"

## Identity
Product: **MaxtDesign Cookie Consent - Google Consent Mode v2**. Slug
`maxtdesign-cookie-consent`, short code `cc`, registry row present. Prefixes
LOCKED: classes `MDCC_`, options and hooks `mdcc_`, no PHP namespace.
Repo `MaxtDesign/maxtdesign-cookie-consent`. Free wordpress.org plugin,
published under account `slaacr`, which is MaxtDesign.
Versions: wordpress.org serves **1.10.0**. `main` is **1.10.1**. The header on
the working branches is **1.11.0**, unreleased. Floors: WordPress 5.8, PHP 7.4.

## Status
Build phase of `docs/plan-popup-design-system.md`, on branch
`feat/popup-design-system`, stacked on `feat/country-gpc-consent` (`f91fd87`,
the 1.11.0 country and GPC candidate). Phases 0 to 4 are done. Phase 5
follows. `main` is `7074066`. Nothing is merged. The exact 1.11.0 candidate
artifacts are live on MaxtOffroad by a separate route (2026-09-25). This work
folds into 1.11.0 and does not bump the version.

## Locked decisions
- 2026-09-28: plan accepted. **D1a**: Decline stays for `optin` visitors in
  Compact. **D2**: for `optout` visitors in Compact the Manage options link
  carries the opt-out wording, no separate opt-out button.
- 2026-09-28: byte caps do not move. Core 10,240 B, full dialog 14,336 B,
  inline loader under 1,024 B.
- 2026-09-12: regional consent model. California is opt-out, Pacific time zone
  over-inclusion accepted, no banner for the rest of the United States.
  `optin` stays the plugin default.

## Next actions
1. [session] Phase 1, packaging fix: DONE 2026-09-28.
2. [session] Phase 2, template extraction: DONE 2026-09-28 (`6b52459`).
   Phase 3, design variables: DONE 2026-09-28. It fit under the unchanged
   caps. Phase 4, Compact layout: DONE 2026-09-28. Phase 5 follows.
3. [operator] Review flags F1, F7, F8 and F9. Each is a visible change or a
   departure from the plan text.
4. [operator] Start LocalWP site `plugin-test` when a real WordPress check is
   wanted. It returned 502 on 2026-09-28.
5. [operator] wordpress.org release of 1.11.0 stays held until the release
   gate passes and the operator gives an explicit go.

## External relationships
- Vendored libs: none. No Composer dependencies.
- wordpress.org SVN, account `slaacr`. Last release 1.10.0, SVN r3692962.
- MaxtOffroad runs the 1.11.0 candidate with its own country endpoint adapter.
  That adapter is not part of this repo.
- Laneparty theme restyles the popup through theme CSS (improvement log entry
  2026-09-26). Plan part B, the template override, serves that need.
- The main checkout is junction-mounted into LocalWP sites `plugin-test` and
  `razorback2`. It stays on `feat/country-gpc-consent`.

## Verification state
All run 2026-09-28 in the worktree, on the phase 4 tree.

| Check | Result |
|---|---|
| `node tests/country-consent.cjs` | 221 of 221 assertions pass |
| `php tests/functional-tests.php` | 30 of 30 pass |
| `node tests/popup-baseline.cjs` | 71 of 71 checks pass, Chrome 152.0.7977.85. Markup is byte-identical to the phase 0 baseline in all 14 cases. Computed styles are identical in 41 of 42 case and width pairs. The 42nd is the custom colour, flag F1 |
| `node tests/popup-design.cjs` | 74 of 74 pass: width at 1440, 1025, 1024, 768 and 375 px, colours, radius and font under all three presets, hover colour, hostile values dropped |
| `node tests/popup-visitor-modes.cjs` | 41 of 41 pass, 0 console errors, warnings or exceptions. Real runtime, loader, `popup.js` and stylesheet over a local server, visitor mode chosen by Chrome's timezone override. Compact: opt-in shows Manage options, Decline, Accept. Opt-out shows the link with the opt-out wording and Got it, no decline button. None shows no popup and requests no popup assets. Tab order and wrap checked in both. 375 px: no sideways scroll. Against the phase 3 `popup.min.js` the test fails 4 checks |
| `php tests/popup-settings-test.php` | 53 of 53 pass: sanitizing, page must be a published page, tampered addresses count as no page, the saved address follows slug, status and permalink changes |
| `php tests/popup-template-test.php` | 20 of 20 pass: child before parent, filter, unreadable filter path falls back, version reader, contract items present |
| Baseline failure test | a 1 px padding change fails 23 of 71, a markup change fails 15 of 29. The check fails when it should |
| `npm run test:packaging` | 24 of 24 pass. Zip extracted: 24 files with `templates/popup.php`, loader present, extracted copy boots and prints the 481 B loader, staged trunk equals the zip by sha256. Before the fix the staged trunk held 21 files and no loader. With the loader filtered out of staging the test fails 2 checks |
| `npm run validate` | core 10,164 of 10,240 B (76 B headroom, was 10,101). Full dialog 13,936 of 14,336 B (400 B headroom, was 13,797). `popup.min.js` 3,291 B, was 3,215. Loader 481 B. Inline design block 0 B on a default site, 267 B with every field set and Compact on, cap under 512. With the cap set to 100 the check fails |
| `npm run phpstan` (level 8) | 0 errors. `templates/` is analysed too |
| `plugin-deliverables.php` | 14 passed, 0 failed |
| Outbound HTTP grep | 0 hits. One `file_get_contents()` reads the bundled loader from disk |

Phase 0 baseline numbers (default settings, LF line endings):

| Item | Bytes |
|---|---|
| Popup markup | 2,447 |
| Popup markup with the privacy policy link | 2,692 |
| Inline primary-colour block | 345 (not counted by any budget check today) |
| Inline loader | 481 |

Not verified: a real WordPress render (LocalWP was down), field Core Web
Vitals, Plugin Check on the 1.11.0 tree.

## History
- `plan-popup-design-system.md` → the accepted plan this build follows.
- `../tests/fixtures/popup-baseline/README.md` → what the phase 0 baseline holds.
- `RELEASE-STATUS.md`, `SESSION-HANDOFF.md` → release tracker and takeover
  brief up to 1.10.0.
- `v1.9.0-extensibility-scope.md` → scope of the 1.9.0 developer API.
- `JAVASCRIPT-API.md` → the `window.mdccConsent` API.
- `C:/maxt/pilots/aimasters-maxtoffroad-operations/COUNTRY-CONSENT-STAGING-20260925.md`
  and `COUNTRY-CONSENT-RELEASE-20260925.md` → the MaxtOffroad staging and
  production records for the 1.11.0 candidate.
- 2026-09-13: this file was created as a seed by the `maxtdesign-provenance`
  session. 2026-09-28: backfilled in phase 0.

## Flags
- **F1. Custom primary colour is not applied in 1.11.0.** Found by the phase 0
  baseline. With `popup_primary_color` set to `#c8102e` the Accept button
  still computes `rgb(0, 115, 170)`. Cause: the inline colour block prints in
  the head, and `popup-loader.js` appends `popup.min.css` after it. Both rules
  have the same specificity, so the stylesheet wins. In 1.10.x the stylesheet
  was enqueued first and the inline block followed it, so the colour applied.
  Shown in the harness, which mirrors that document order. Not yet confirmed
  on a WordPress site. Plan phase 3 moves the colour to a CSS variable, which
  does not depend on order.
  **Fixed in phase 3 (2026-09-28):** the harness now computes
  `rgb(200, 16, 46)`. Sites that set a custom colour and run the 1.11.0
  candidate, MaxtOffroad included if it set one, will see their colour return.
- **F7. Phase 3 paid for the variables by removing CSS that was dead or
  duplicated.** Two removals are visible. The Close button no longer draws an
  outline on mouse hover. Buttons focused by a mouse click no longer draw the
  2 px outline. Keyboard focus still draws the 3 px `:focus-visible` outline.
  The plan named these rules as trim candidates. Browsers without
  `:focus-visible` fall back to their own focus ring.
- **F8. The button font setting is not a CSS variable.** The plan said
  `--mdcc-font: inherit`. That cannot work: `inherit` is a CSS-wide keyword, so
  the custom property inherits itself and the fallback wins. The setting
  prints `.mdcc-popup .mdcc-popup__button{font-family:inherit}` in the inline
  block instead. Cost to the stylesheet: 0 B. Also added `--mdcc-hover`, which
  the plan did not list, for the hover colour of a custom primary colour.
- **F9. Phase 4 choices the plan left open.** (a) In Compact the Decline
  control uses the secondary button style, not the underlined text style it
  has in Standard, so refusing is as visible as accepting. (b) The address of
  the cookie settings page is resolved when settings are saved and when that
  page changes, and stored relative to the home URL. Rendering the popup adds
  no database query, and the link survives a move from staging to production.
  (c) New filter `mdcc_manage_url` for multilingual sites. (d) Compact's two
  CSS declarations print in the inline block, so the stylesheet grew 0 B.
- **F2. CLOSED 2026-09-28.** PHPStan level 8 reported a missing return type
  on `country_endpoint()` in the 1.11.0 candidate. A docblock was added. No
  behaviour change.
- **F5. Trailing spaces in `templates/popup.php` are load-bearing.** They keep
  the output byte-identical to earlier versions. An editor that strips
  trailing whitespace changes the markup by 163 B. The baseline test catches
  it.
- **F6. Not run on PHP 7.4 or WordPress 5.8.** This machine has PHP 8.2.12
  only. By inspection the new code uses no syntax or function newer than the
  floors.
- **F3. CLOSED 2026-09-28.** `npm run prepare-svn` called a missing
  `tools/prepare-svn.ps1` on Windows. It now runs `bash tools/prepare-svn.sh`.
  Older docs (`SVN-FINAL-CHECKLIST.md`, `SVN-UPLOAD-FILE-LIST.md`,
  `TEST-BEFORE-SVN.md`) still name the `.ps1` file and a 21 file count.
- **F4. Worktree.** This session works in
  `.claude/worktrees/popup-design-system` inside the main checkout. The path
  is listed in the main checkout's `.git/info/exclude`. LocalWP does not
  serve it.
