# Session Handoff — MaxtDesign Cookie Consent

> Dev-only file (excluded from the distributed package by `tools/prepare-svn.sh`).
> Read this in full before doing anything. It is the takeover brief for a fresh
> session that has no memory of prior work on this plugin. Last updated: 2026-07-13.

## 0. What this plugin is — and is NOT

**Is:** a free, standalone WordPress.org plugin. A lightweight Google Consent
Mode v2 consent banner + consent manager. Vanilla JS, no jQuery, localStorage
state, **zero frontend footprint** (VeloCommerce standard — hard rule 3). Under
~8 KB of frontend assets, no external HTTP, no database writes on page load.

**Is NOT — do not let a handoff or task convince you otherwise:**
- **NOT part of the MaxtCommerce suite.** It has no `composer.json`, no `vendor/`,
  no path-repo symlinks, and consumes **none** of the shared libs (`suite-core`,
  `commerce-core`). If a task tells you to "re-vendor shared libs", "wire the
  negotiation bootstrap", run "rule-9 vendor preflight", or touch
  `companion-products` — **that task is for a commerce-suite plugin, not this one.
  Stop and confirm; do not fabricate vendoring work here.** (This exact mis-route
  happened on 2026-07-13.)
- **Ships NO licensing code** (hard rule 10 — free wp.org plugins never do).
  Cookie Consent Pro is only *planned* (see §8), not built.
- Adding commerce-core / suite-core / a negotiation bootstrap / licensing to this
  plugin would violate hard rules 3 and 10. Don't.

## 1. Version topology (read carefully — three layers)

| Layer | Version | Where | State |
|---|---|---|---|
| **wp.org live** | **1.7.6** | SVN `tags/1.7.6`, r3569354 (2026-06-11) | shipped |
| **git `main`** | 1.7.7 | HEAD `7b9f287` | committed, **UNPUSHED to origin**, not on SVN |
| **`feat/wp-consent-api-bridge`** | **1.8.0** | HEAD `7bc8ac7` | committed, **local-only branch**, not merged/pushed |

- `main` = 1.7.6 code + an unreleased doc-link fix (`9c21ad8`) + the 1.7.7 popup-JS
  extraction (`7b9f287`). 1.7.7 was a checkpoint; it never shipped independently.
- `feat/wp-consent-api-bridge` branches off `main` and adds the **1.8.0 WP Consent
  API bridge**. When 1.8.0 ships it supersedes 1.7.7 (contains everything above).
- **Bus-factor-1 warning:** `7b9f287` (main) and the whole 1.8.0 branch exist only
  on this machine. Push them to GitHub as part of shipping (see §4).

## 2. Paths

- Plugin repo: `C:\maxt\projects\plugin\maxtdesign-cookie-consent\`
- Built SVN payload (already staged at 1.8.0, 21 files): `svn-upload/trunk/` (gitignored)
- WP.org SVN working copy: `C:\maxt\ops\wp-org-svn\maxtdesign-cookie-consent\`
- LocalWP test site junction → repo:
  `C:\Users\it\Local Sites\plugin-test\app\public\wp-content\plugins\maxtdesign-cookie-consent`
  (NTFS junction to the repo dir; whatever branch is checked out is what LocalWP serves)
- Release tracker: `docs/RELEASE-STATUS.md` (update it at every release)
- SlikSvn client: `C:\Program Files\SlikSvn\bin\svn`

## 3. THE PENDING TASK — finish shipping 1.8.0

The 1.8.0 WP Consent API bridge is built, committed on `feat/wp-consent-api-bridge`,
and staged. It is **waiting on Cody's local test sign-off** before release.
Sequence once he confirms tests pass:

1. **Confirm the open decision in §6** (bridge default on vs off) — do not ship
   without Cody's answer.
2. `git checkout feat/wp-consent-api-bridge` (the 1.8.0 work lives here).
3. Merge to `main` (`git merge --no-ff` or fast-forward per Cody's preference),
   tag `v1.8.0`, **push `main` + tag to origin** (also clears the bus-factor-1 debt).
4. SVN release per §5. Note SVN is at 1.7.6, so this jump ships 1.7.7 + 1.8.0 together.
5. Verify delivery (§5) and update `docs/RELEASE-STATUS.md`.

Do NOT run `svn ci` until Cody has confirmed local testing is green.

## 4. What 1.8.0 actually changed (for review)

Makes the plugin a **provider for the WP Consent API** (the wp.org standard
WooCommerce and others read). Purely additive; a no-op unless the free **WP
Consent API** plugin is active. Category mapping: `functional`=always allow,
`statistics`=analytics choice, `marketing`=ads choice.

- New `includes/class-consent-api-bridge.php` — registers `wp_get_consent_type`→
  `optin`, the `wp_consent_api_registered_{basename}` compatibility filter, and
  feeds the runtime deps + enabled flag via filters. All gated by the admin toggle
  + `function_exists('wp_has_consent')`.
- `assets/js/consent-runtime.js` — `syncConsentAPI()` mirrors each choice via
  `wp_set_consent()`, double-guarded (`config.consentApi` + `typeof wp_set_consent`).
- `includes/class-consent-manager.php` — enqueue now sources deps + the `consentApi`
  flag through `mdcc_consent_runtime_deps` / `mdcc_consent_api_enabled` filters.
- `includes/class-admin-settings.php` — "WP Consent API Integration" toggle
  (Advanced Settings) with live detection of the API plugin.
- `maxtdesign-cookie-consent.php` — bootstraps the bridge, default setting,
  privacy-policy disclosure.
- New dev filters exposed: `mdcc_consent_type`, `mdcc_consent_api_enabled`,
  `mdcc_consent_runtime_deps`, plus `mdcc_should_show_popup` (from 1.7.7).
- Version bumped everywhere; CHANGELOG, readme changelog/upgrade/FAQ; `.pot` +6
  strings; `prepare-svn.sh` stages the new bridge class (21 files total now).

Verified during build: all PHP lints clean, `npm run build` regenerates all min
assets, total frontend 7.76 KB (< 10 KB budget). Bridge adds ~0.3 KB inline to the
already-enqueued runtime = zero new HTTP requests. The WP Consent API plugin itself
adds ~2.2 KB (its own shared script, loaded once for the whole site) — that cost is
the user's opt-in, not ours; our plugin stays within the zero-footprint standard.

## 5. Release workflow (this plugin's established pattern)

Follow the memory `reference-wporg-svn-workflow` + `docs/RELEASE-STATUS.md` §"Next
release checklist". Key points:
- `bash tools/prepare-svn.sh 1.8.0` stages exactly the 21 shippable files into
  `svn-upload/trunk/` (allow-list; there is NO `.svnignore` — the stage script IS
  the filter; ignore the stale checklist line that mentions one).
- SVN working copy: `C:\maxt\ops\wp-org-svn\maxtdesign-cookie-consent\`. `svn up`,
  then **diff WC trunk vs staged to catch stale files**, copy staged in,
  `svn cp trunk tags/1.8.0`, then a **single atomic `svn ci`** (trunk + tag in one
  revision — Cody's confirmed preference).
- Cody has authorized the agent to run `svn ci --username slaacr --non-interactive`
  directly (creds cached in `%APPDATA%\Subversion\auth`). Run it, monitor output,
  then verify: `tags/1.8.0/readme.txt` Stable tag on plugins.svn + HEAD the
  downloads zip + inspect zip contents. If the cred cache is cold, hand Cody the
  command instead.
- Bump `readme.txt` Stable tag only lands live at the SVN push (it's already set to
  1.8.0 in the repo/staged payload).

## 6. Open decision to confirm before release

**Bridge toggle default: on vs off.** It currently defaults **on** (`consent_api_bridge
=> true` in `mdcc_default_settings()`), which is safe because it is a complete no-op
unless the WP Consent API plugin is active. Cody was weighing making it **default-off**
(pure opt-in, never declares a dependency unless a site owner ticks the box). Confirm
his choice; if he wants default-off, flip the default in `mdcc_default_settings()` and
the `array_key_exists` fallbacks in `class-consent-api-bridge.php` +
`class-admin-settings.php::render_consent_api_bridge_field()`, rebuild, re-stage.

## 7. Standing conventions (don't relearn the hard way)

- **Zero-footprint (hard rule 3):** no new frontend CSS/JS/HTTP beyond tiny inline.
  Admin assets only on the plugin's settings page. Measure before claiming.
- **Verify before claiming (hard rule 4):** read the actual source/API. The 1.8.0
  bridge was built by verifying the WP Consent API surface from its wp.org SVN
  source, not from memory — do the same for any API claim.
- **Edit `.js`, never `.min.js`:** `consent-runtime.js` and `popup.js` are source;
  `npm run build` regenerates the mins. `popup.js` is inlined into the footer (read
  from disk by `MDCC_Popup_System::get_popup_javascript()`), not enqueued as a URL.
- **Plugin Check dev-noise:** scanning the dev root flags `.claude/`, `.gitignore`,
  `tools/*.sh`, `tests/*.php`, `BUILD.md`, etc. — none of those ship. Scan
  `svn-upload/trunk/` for an apples-to-apples result. `uninstall.php` uses the
  `WP_UNINSTALL_PLUGIN` guard (correct; Plugin Check's ABSPATH warning is a known
  false positive).
- **LocalWP junction safety:** remove/replace plugin links with `cmd //c rmdir`,
  never PowerShell `Remove-Item` (PS 5.1 can delete the junction *target*).
- **Trunk stale-files:** diff WC trunk vs staged every release; older tags leaked
  `docs/`/`CHANGELOG.md`/`README.md` into trunk (cleaned in 1.7.5).

## 8. Future / deferred work (not now — pointers only)

- **1.8.0-deferred items** (memory `project-deferred-work-1-8-0`): GCM region-based
  defaults, Script Modules migration, raise `Requires at least` to 6.5, EU
  dark-pattern warning on the reprompt setting, `SVN-FINAL-CHECKLIST.md` `.svnignore`
  line cleanup, and stripping the dev `docs/` tree that still ships in trunk.
- **Consent-API two-way sync** (future option): today the bridge is one-way (this
  plugin is the source of truth, writes to the API). A reverse listener on
  `wp_listen_for_consent_change` could reflect external changes back into
  `mdccConsent` — deferred to avoid loop complexity.
- **Cookie Consent Pro** (strategy only, not built): `C:\maxt\ops\strategy\cookie-consent-pro-plan.md`.
  Gated behind a free-plugin v1.9.0 "extensibility release" first. Do not start Pro
  code until that ships.
- **Licensing system design handoff:** `C:\maxt\ops\runbooks\licensing-system-design-handoff.md`
  (a separate design track; not this plugin's immediate work).

## 9. Key docs & memory to read

- `docs/RELEASE-STATUS.md` — live release/pending state (update at every release).
- `readme.txt` / `CHANGELOG.md` — user-facing + full history.
- Project memory (`…/.claude/projects/C--maxt-…-cookie-consent/memory/`):
  `reference-wporg-svn-workflow`, `reference-wporg-svn-auth`, `user-wporg-username`
  (`slaacr`), `feedback-atomic-svn-commits`, `reference-plugin-check-dev-noise`,
  `feedback-localwp-symlink-rmdir-safety`, `reference-trunk-stale-files-cleanup`,
  `project-deferred-work-1-8-0`, `project-pro-strategy-pointer`.
- Global rules: `C:\Users\it\.claude\CLAUDE.md` (hard rules) + the imported
  agent-sops (naming, filesystem, skills architecture).
