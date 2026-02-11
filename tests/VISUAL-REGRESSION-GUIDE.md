# Visual Regression Testing Guide

## Purpose
Ensure that CSS refactoring (Phase 1) has not introduced ANY visual changes to the popup appearance.

## Requirements
- Browser: Chrome (recommended for consistency)
- Screen: 1920x1080 resolution
- Extensions: Full Page Screen Capture or similar
- Image diff tool: ImageMagick `compare` or online tool like diffchecker.com

---

## Before Optimization (Baseline Screenshots)

### Setup
1. Checkout pre-optimization commit (before Phase 1)
2. Clear browser cache
3. Clear localStorage
4. Load test page

### Screenshot List (9 total)

**Minimal Style:**
1. `minimal-top-BEFORE.png` - Minimal + Top position
2. `minimal-bottom-BEFORE.png` - Minimal + Bottom position
3. `minimal-center-BEFORE.png` - Minimal + Center position

**Modern Style:**
4. `modern-top-BEFORE.png` - Modern + Top position
5. `modern-bottom-BEFORE.png` - Modern + Bottom position
6. `modern-center-BEFORE.png` - Modern + Center position

**Bold Style:**
7. `bold-top-BEFORE.png` - Bold + Top position
8. `bold-bottom-BEFORE.png` - Bold + Bottom position
9. `bold-center-BEFORE.png` - Bold + Center position

### Screenshot Settings
- Full page screenshot
- Include browser chrome: NO
- Show popup: YES
- Animation: Complete (wait 1 second after load)

---

## After Optimization (Comparison Screenshots)

### Setup
1. Checkout post-optimization commit (after Phase 1-3)
2. Run build: `npm run build`
3. Clear browser cache
4. Clear localStorage
5. Load test page

### Screenshot List (same 9)

**Minimal Style:**
1. `minimal-top-AFTER.png`
2. `minimal-bottom-AFTER.png`
3. `minimal-center-AFTER.png`

**Modern Style:**
4. `modern-top-AFTER.png`
5. `modern-bottom-AFTER.png`
6. `modern-center-AFTER.png`

**Bold Style:**
7. `bold-top-AFTER.png`
8. `bold-bottom-AFTER.png`
9. `bold-center-AFTER.png`

---

## Comparison Method

### Using ImageMagick (Command Line)

```bash
# Compare two screenshots
compare -metric RMSE \
  minimal-top-BEFORE.png \
  minimal-top-AFTER.png \
  minimal-top-DIFF.png

# Output: 0 (0) = perfect match
# Output: >0 = differences exist
```

**Acceptable threshold:** RMSE < 0.01 (essentially zero differences)

### Using Online Tool

1. Go to https://diffchecker.com/image-diff/
2. Upload BEFORE screenshot
3. Upload AFTER screenshot
4. Review highlighted differences

**Expected:** Zero differences (100% identical)

---

## Manual Visual Inspection Checklist

Even with automated comparison, visually inspect each screenshot pair:

**For EACH screenshot pair:**
- [ ] Popup position identical (top/bottom/center)
- [ ] Button placement identical
- [ ] Text alignment identical
- [ ] Font sizes identical
- [ ] Colors identical (buttons, text, background)
- [ ] Spacing/padding identical
- [ ] Border radius identical (if applicable)
- [ ] Box shadow identical (if applicable)
- [ ] Border thickness identical (if applicable)
- [ ] No new rendering artifacts
- [ ] No missing elements
- [ ] No shifted elements

---

## Failure Criteria

**If ANY of these are true, optimization FAILED:**
- RMSE > 0.01 in automated comparison
- Visible differences in manual inspection
- Button position changed
- Text alignment changed
- Colors different
- Spacing changed
- Border radius changed
- Shadow different

**Action if failed:**
- Review Phase 1 CSS changes
- Identify which CSS rule caused the change
- Revert or fix the problematic rule
- Re-run build and re-test

---

## Success Criteria

**All 9 screenshot pairs:**
- [ ] ✅ RMSE = 0 (or < 0.01)
- [ ] ✅ No visible differences
- [ ] ✅ Manual inspection passes

**Sign-off:**
Visual regression testing complete. All screenshots match. No visual changes detected.

**Tested by:** _____________________  
**Date:** _____________________
