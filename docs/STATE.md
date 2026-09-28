# STATE: maxtdesign-cookie-consent

## September25 production release amendment

Owner approved and exact1.11.0 artifacts are now live on MaxtOffroad, alongside
its separate country endpoint/adapter and policy wording. Independent hashes,
endpoint HTTP checks and live preference/decline/reload passed. See
`C:/maxt/pilots/aimasters-maxtoffroad-operations/COUNTRY-CONSENT-RELEASE-20260925.md`.
This supersedes staging-only/pending-approval language below for that store.
Shared source remains uncommitted; WordPress.org/general distribution unchanged.

## September 25, 2026 amendment — unpublished country/GPC candidate

Codex prepared 1.11.0 for MaxtOffroad's authorized staging work. Source baseline
was clean at7074066 (main header1.10.1); deployed store baseline was1.10.0 with
the same reviewed runtime source. Candidate is uncommitted/unpublished. No
WordPress.org or general distribution release was performed.

Adds optional `mdcc_country_endpoint` filter accepting a root-relative path.
Endpoint must be same-origin, uncached, respond JSON `{mode:"none"|"optin"}`,
and derive policy from a trusted server-side source outside full-page cache.
The plugin has no default country service or U.S.-specific business policy.
Requests time out after2seconds; unknown/failure is opt-in. Use `mdccConsent.ready`
for UI that waits on policy resolution. Saved choices and GPC skip lookup.
GPC denies effective state even with a prior grant; popup assets lazy load via
an under1KB bootstrap. Production builds omit debug calls; SCRIPT_DEBUG uses
readable source. Cached popup markup is now independent of visitor cookies.

221 focused assertions and30 existing functional checks pass; asset budgets
pass. Real staging endpoint, browser fixtures, mobile keyboard/layout, hashes
and preserved guards were checked. Full field CWV and real foreign-egress cache
behavior remain unverified. Exact report/rollback/immutable artifacts:
`C:/maxt/pilots/aimasters-maxtoffroad-operations/COUNTRY-CONSENT-STAGING-20260925.md`.
Production is unchanged and exact release approval is pending. No active writer
after this handoff; recheck ownership before edits. Historical seed below is
dated evidence, not current deployment status.

Updated: 2026-09-13 by session

## Identity
Product: **MaxtDesign Cookie Consent, Google Consent Mode v2**. Version 1.10.0.
Distribution: wordpress.org, slug `maxtdesign-cookie-consent`, ~70 active installs (API, 2026-09-13).
Published under wp.org account `slaacr`, which is MaxtDesign. The username predates the brand and
wordpress.org does not support renaming it.

## Status
**This file is a seed.** It was created 2026-09-13 by the `maxtdesign-provenance` session purely so
the provenance and CRA baseline below is not lost, because this repo had no STATE.md and the
tracking-notes standard requires one. Everything outside Identity and Next actions is unfilled.
The next real session on this plugin should backfill it properly rather than trust its silence.

## Locked decisions
None recorded. Backfill needed.

## Next actions
1. [session] **Backfill this file** from the repo and its history before relying on it.
2. [session] **Provenance and CRA baseline, at next release.** Audited 2026-09-13. No `SECURITY.md`. Copy the well-formed one in `maxtdesign-disable-rest-api`. Add a
   security contact line to `readme.txt` and generate an SBOM for the release. The MaxtDesign CRA
   article tells readers to do both and calls the security contact "the single thing most likely to
   be asked for first"; that article is public from 2026-09-22, so the advice is on the record. The
   same line must state that the wordpress.org account `slaacr` is MaxtDesign, for the reason in
   Identity above: disclosed, not fixed. Canonical checklist, ranked costs and audit method:
   `C:/maxt/projects/saas/maxtdesign-provenance/docs/self-application-plan.md`.

## External relationships
Unfilled. Backfill needed.

## Verification state
Unfilled. No claims made here rather than stale ones.

## History
- `docs/STATE.md` (2026-09-13) → this seed file.

## Flags
- **Seed file.** Created by a session that did not work on this plugin. Do not read absence of
  content as absence of state. Mirrored to the central improvement log as part of the
  provenance-baseline flag.
