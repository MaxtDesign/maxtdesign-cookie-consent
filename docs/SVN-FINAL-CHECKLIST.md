# Final Pre-SVN Upload Checklist

This checklist must be completed before uploading to WordPress.org SVN.

## Files Removed ✓
- [ ] check-sizes.ps1
- [ ] create-wordpress-symlink.ps1
- [ ] tools/build.ps1
- [ ] tools/prepare-svn.ps1
- [ ] assets/css/popup.css.backup
- [ ] assets/js/consent-runtime.js.backup

## Documentation Links Updated ✓
- [ ] Plugin header has GitHub sponsor link
- [ ] Admin page documentation links point to WordPress.org
- [ ] Elementor integration link updated
- [ ] readme.txt has donate link
- [ ] FAQ entry for Elementor Popup ID added

## File Verification ✓
- [ ] No .ps1 files in directory
- [ ] No .backup files in directory
- [ ] No .notes directory in SVN upload
- [ ] No .github directory in SVN upload
- [ ] No node_modules in SVN upload
- [ ] .svnignore is comprehensive

## Link Verification ✓
- [ ] All links in admin page work
- [ ] All links in readme.txt work
- [ ] GitHub sponsor link correct: https://github.com/sponsors/MaxtDesign
- [ ] Author URI correct: https://github.com/MaxtDesign

## Code Quality ✓
- [ ] Version number consistent across files
- [ ] All text properly escaped
- [ ] All inputs properly sanitized
- [ ] No PHP errors with WP_DEBUG enabled
- [ ] No JavaScript console errors

## Testing ✓
- [ ] Popup appears correctly
- [ ] Consent buttons work
- [ ] Settings save correctly
- [ ] Shortcodes render correctly
- [ ] GCM default injection working
- [ ] No conflicts with common plugins

## SVN Preparation ✓
- [ ] Run: `svn status` to see what will be committed
- [ ] Verify no unwanted files listed
- [ ] Verify .svnignore is working
- [ ] All wanted files are included

## Ready to Upload
- [ ] All checklist items completed
- [ ] Final review with fresh eyes
- [ ] Commit to SVN trunk
