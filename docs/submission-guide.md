# WordPress.org Submission Guide

## MaxtDesign Cookie Consent - Google Consent Mode v2 v1.6.0

### Pre-Submission Checklist
- [x] Plugin fully developed and tested
- [x] Validation checks prepared (Plugin Check, PHPCS)
- [x] Distribution ZIP to be created
- [x] Fresh install test plan ready
- [x] Documentation complete

### Submission Process

**Step 1: Create WordPress.org Account**
1. Go to https://wordpress.org/
2. Create account or log in
3. Verify email address

**Step 2: Submit Plugin**
1. Go to https://wordpress.org/plugins/developers/add/
2. Upload: `maxtdesign-cookie-consent-1.6.0.zip`
3. Fill in description (copy from `readme.txt`)
4. Submit for review

**Step 3: Wait for Approval**
- Typical wait time: 3–21 days
- Check email daily for feedback (from plugins@wordpress.org)

**Step 4: Respond to Review Feedback**
If changes requested:
1. Make required changes
2. Update version if needed
3. Create new ZIP
4. Reply to review email with changelog

**Step 5: SVN Setup (upon approval)**
```bash
svn co https://plugins.svn.wordpress.org/maxtdesign-cookie-consent
# structure: /trunk, /tags, /assets
```

**Step 6: Upload Files**
```bash
# Copy plugin files to trunk
cp -r /path/to/plugin/* trunk/
svn add trunk/*
svn ci -m "Initial commit of version 1.6.0"
```

**Step 7: Tag Release**
```bash
svn cp trunk tags/1.6.0
svn ci -m "Tagging version 1.6.0"
```

**Step 8: Upload Assets (after approval)**
- icon-128x128.png, icon-256x256.png
- banner-772x250.png, banner-1544x500.png
- screenshot-1.png .. screenshot-6.png
```bash
cp /path/to/assets/* assets/
svn add assets/*
svn ci -m "Add plugin assets"
```

**Step 9: Verify Plugin Page**
1. Visit https://wordpress.org/plugins/maxtdesign-cookie-consent
2. Verify details and downloads

**Step 10: Initial Monitoring**
- Monitor support forum
- Watch download/activation metrics
