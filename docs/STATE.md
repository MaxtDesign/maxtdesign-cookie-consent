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
The popup design system plan is **built, all six phases**, on branch
`feat/popup-design-system`, pushed. It is stacked on
`feat/country-gpc-consent` (`f91fd87`, the 1.11.0 country and GPC candidate).
It is **not merged, not released and not deployed**. It has not been rendered
by WordPress yet. `main` is `7074066`. The 1.11.0 candidate artifacts, without
this work, are live on MaxtOffroad by a separate route (2026-09-25). This work
folds into 1.11.0 and does not bump the version.

Full record with numbers, flags and review results:
`build-report-popup-design-system.md`.

## Locked decisions
- 2026-09-28: plan accepted. **D1a**: Decline stays for `optin` visitors in
  Compact. **D2**: for `optout` visitors in Compact the Manage options link
  carries the opt-out wording, no separate opt-out button.
- 2026-09-28: byte caps do not move. Core 10,240 B, full dialog 14,336 B,
  inline loader under 1,024 B, inline design block under 512 B.
- 2026-09-12: regional consent model. California is opt-out, Pacific time zone
  over-inclusion accepted, no banner for the rest of the United States.
  `optin` stays the plugin default.

## Next actions
1. [operator] Read the flags in the build report. F1, F7, F8, F9, F11 and F12
   are visible changes or departures from the plan text.
2. [operator] Give a go, or not, to switch the main checkout to
   `feat/popup-design-system`. LocalWP serves that folder to `plugin-test`
   and to `razorback2`, the MaxtOffroad staging site. Until then no WordPress
   render of this work exists.
3. [operator] Start LocalWP site `plugin-test`. It returned 502 on 2026-09-28.
4. [session] After 2 and 3: check the settings screen and the popup in a
   browser on `plugin-test`, then Plugin Check on a staged trunk under the
   real slug. The operator swaps the junction.
5. [session] Open a pull request. The branch is stacked, so
   `feat/country-gpc-consent` goes to `main` first, or both go together.
6. [operator] wordpress.org release of 1.11.0 stays held until
   `release-gate.php` passes and the operator gives an explicit go.
7. [session] Later: regenerate the `.pot`, refresh the stale documents in
   flag F10.

## External relationships
- Vendored libs: none. No Composer dependencies.
- wordpress.org SVN, account `slaacr`. Last release 1.10.0, SVN r3692962.
- MaxtOffroad runs the 1.11.0 candidate with its own country endpoint adapter.
  That adapter is not part of this repo.
- Laneparty theme restyles the popup through theme CSS (improvement log entry
  2026-09-26). The template override and the CSS variables serve that need.
- The main checkout is junction-mounted into LocalWP sites `plugin-test` and
  `razorback2`. It stays on `feat/country-gpc-consent`.

## Verification state
All run 2026-09-28 in the worktree on the final tree. Details and the failure
tests of each check are in the build report.

| Check | Result |
|---|---|
| Tests | 221 country consent, 30 functional, 71 baseline, 74 design, 41 visitor modes, 60 settings, 20 template, 24 packaging. All pass |
| Default rendering | markup byte-identical to `f91fd87` in 14 of 14 cases. Computed styles identical in 41 of 42. The 42nd is the custom colour fix |
| Budgets | core 10,168 of 10,240 B. Full dialog 13,942 of 14,336 B. Loader 481 B. Inline design block 0 B by default, 267 B with every field set |
| PHPStan level 8 | 0 errors |
| `plugin-deliverables.php` | 14 passed, 0 failed |
| Security audit | 0 Critical, 0 High, 0 Medium. 4 Low, all fixed |
| Code review | 0 Block. 6 Fix-before-merge, all fixed. The fixes were not reviewed again |
| Outbound HTTP | 0 |

Not verified: a render by WordPress, the settings screen in a browser, Plugin
Check, `release-gate.php`, PHP 7.4 and WordPress 5.8 at runtime, field Core
Web Vitals, screen reader behaviour.

## History
- `plan-popup-design-system.md` → the accepted plan.
- `build-report-popup-design-system.md` → what was built, measured, flagged
  and reviewed, 2026-09-28.
- `../tests/fixtures/popup-baseline/README.md` → the rendering baseline and
  its intended changes.
- `user-guide/features/popup-design.md` → setup guide for site owners.
- `JAVASCRIPT-API.md` → the `window.mdccConsent` API and the popup template
  contract.
- `RELEASE-STATUS.md`, `SESSION-HANDOFF.md` → release tracker and takeover
  brief up to 1.10.0.
- `v1.9.0-extensibility-scope.md` → scope of the 1.9.0 developer API.
- `C:/maxt/pilots/aimasters-maxtoffroad-operations/COUNTRY-CONSENT-STAGING-20260925.md`
  and `COUNTRY-CONSENT-RELEASE-20260925.md` → the MaxtOffroad records for the
  1.11.0 candidate.
- 2026-09-13: this file was created as a seed. 2026-09-28: backfilled.

## Flags
Detail for F1 to F12 is in the build report. Open for the operator:
- **F1.** A custom Primary Color was not applied in the 1.11.0 candidate. It
  is again.
- **F7.** No outline on mouse hover of Close, none on mouse focus of buttons.
  Keyboard focus is outlined as before.
- **F8.** The button font setting prints a rule, not a variable. `--mdcc-hover`
  was added.
- **F9.** Compact: Decline uses the secondary button style. The page address
  is stored at save time. New filter `mdcc_manage_url`.
- **F11.** The default hover colour follows 1.10.x. MaxtOffroad will see it
  change from the candidate's.
- **F12.** In Compact an opt-out visitor opts out in two steps. This is D2 as
  locked. The security audit asked for a confirmation.
- **F5.** Trailing spaces in `templates/popup.php` are load-bearing.
- **F6.** Not run on PHP 7.4 or WordPress 5.8.
- **F10.** Stale documents and the `.pot`, not touched.
- **F4. Worktree.** The work sits in `.claude/worktrees/popup-design-system`
  inside the main checkout, listed in that checkout's `.git/info/exclude`.
  Everything is pushed. Remove it with `git worktree remove` when the main
  checkout takes the branch.
- Mirrored to the improvement log 2026-09-28: a hand-written file list in
  `tools/prepare-svn.sh` can leave a new file out of a release. Other plugins
  that copied this tooling may carry the same gap.
