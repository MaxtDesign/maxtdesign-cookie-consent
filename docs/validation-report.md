# Validation Report (Pre-Submission)

Plugin: MaxtDesign Cookie Consent - Google Consent Mode v2 v1.6.0
Date: 2025-11-10

## Automated Tools

- Plugin Check (WP-CLI / admin): Pending — to run in WP test site
- PHPCS (WordPress standards): Pending — run in dev environment

## Security Audit

Checklist status:
- Input sanitization: PASS (Settings API + sanitize_* calls)
- Output escaping: PASS (esc_html/attr/url, sanitized defaults)
- DB queries: PASS (Options API only, zero custom queries)
- Nonces/Capabilities: PASS (Settings API + manage_options)
- Direct access protections: PASS (ABSPATH/WP_UNINSTALL_PLUGIN checks)

## Performance

- Asset sizes (uncompressed):
  - assets/css/admin.css: ~1.4KB
  - assets/css/popup.css: ~10.0KB
  - assets/js/admin.js: ~0.4KB
  - assets/js/consent-runtime.js: ~6.5KB
  - Total: ~18.4KB (Action required to meet <10KB target)
- Frontend DB queries: 0 expected (verify with Query Monitor)

Remediation plan to meet <10KB uncompressed:
1) Minify CSS/JS (strip comments/whitespace)
2) Trim popup.css by consolidating rules and reducing preset overhead (e.g., simplify Bold/Modern variants)
3) Re-measure and iterate to <=10KB total

## Browser & Device Testing

Matrix and checklist prepared (see user story). Execute on clean test site after ZIP build.

## Accessibility

- ARIA roles/labels present
- Focus trap and keyboard handling implemented
- Contrast and reduced motion considerations present
- To verify with Axe/WAVE during manual test

## Version Consistency

- Main plugin header: 1.6.0 — OK
- MDCC_VERSION constant: 1.6.0 — OK
- readme.txt stable tag: 1.6.0 — OK
- POT: 1.6.0 — OK

## Packaging

- Distribution ZIP: Pending
- Exclude dev files (docs/, site-consent-ORIGINAL.php, svn-upload/, etc.)

## Next Actions

- Run Plugin Check + PHPCS
- Minify/trim assets to hit <10KB
- Build clean ZIP
- Fresh install test and full checklist pass
