# Manual Testing Checklist - MaxtDesign Cookie Consent

## Pre-Testing Setup

### Test Environment
- [ ] Fresh WordPress 5.8+ installation
- [ ] PHP 7.4+ confirmed
- [ ] No other cookie consent plugins active
- [ ] Browser: Chrome/Firefox/Safari (test all)
- [ ] Clear browser cache and cookies before each test
- [ ] Clear localStorage: `localStorage.clear()` in console

### Plugin Installation
- [ ] Plugin activated successfully
- [ ] No PHP errors in WordPress debug log
- [ ] No JavaScript console errors
- [ ] Admin settings page loads correctly

---

## Performance Validation

### Asset Loading (Production Mode)
**Setup:** `define( 'SCRIPT_DEBUG', false );` in wp-config.php

- [ ] Open site, check Network tab in DevTools
- [ ] Verify `popup.min.css` loads (NOT popup.css)
- [ ] Verify `consent-runtime.min.js` loads (NOT consent-runtime.js)
- [ ] popup.min.css size: ≤6KB
- [ ] consent-runtime.min.js size: ≤4KB
- [ ] Total: ≤10KB
- [ ] Gzipped sizes (if server supports): ~3-4KB total

### Asset Loading (Development Mode)
**Setup:** `define( 'SCRIPT_DEBUG', true );` in wp-config.php

- [ ] Open site, check Network tab
- [ ] Verify `popup.css` loads (NOT popup.min.css)
- [ ] Verify `consent-runtime.js` loads (NOT consent-runtime.min.js)
- [ ] Functionality works identically to production mode

---

## Visual Regression Testing

Test ALL 9 combinations (3 styles × 3 positions):

### Minimal Style
- [ ] Minimal + Top position → Matches original exactly
- [ ] Minimal + Bottom position → Matches original exactly
- [ ] Minimal + Center position → Matches original exactly
- [ ] All buttons render correctly
- [ ] Text is readable
- [ ] Colors match theme settings
- [ ] Spacing/padding correct

### Modern Style
- [ ] Modern + Top position → Matches original exactly
- [ ] Modern + Bottom position → Matches original exactly
- [ ] Modern + Center position → Matches original exactly
- [ ] Border radius correct (8px)
- [ ] Box shadow visible
- [ ] All buttons render correctly
- [ ] Colors match theme settings

### Bold Style
- [ ] Bold + Top position → Matches original exactly
- [ ] Bold + Bottom position → Matches original exactly
- [ ] Bold + Center position → Matches original exactly
- [ ] Border visible and correct color
- [ ] Font weight bolder (600)
- [ ] Button padding larger
- [ ] Colors match theme settings

### Animation Testing
For EACH style+position combination:

**Slide Animation:**
- [ ] Top → Slides in from top smoothly
- [ ] Bottom → Slides in from bottom smoothly
- [ ] Center → Fades and scales in smoothly
- [ ] No jank or stuttering
- [ ] Smooth 0.3s transition

**Fade Animation:**
- [ ] Opacity transition smooth
- [ ] No position shifting
- [ ] Smooth 0.3s transition

**No Animation:**
- [ ] Popup appears instantly
- [ ] No transition applied

---

## Responsive Testing

Test each style at these breakpoints:

### Desktop (1024px+)
- [ ] Full layout displayed
- [ ] Buttons side-by-side
- [ ] Text readable
- [ ] No overflow
- [ ] Correct positioning (top/bottom/center)

### Tablet (768px - 1023px)
- [ ] Layout maintained
- [ ] Buttons still side-by-side (or adjusted responsively)
- [ ] Text readable
- [ ] No horizontal scroll

### Mobile (320px - 767px)
- [ ] Buttons stack vertically
- [ ] Text scales down appropriately
- [ ] Full width (with margin)
- [ ] Touch targets ≥44px
- [ ] No horizontal scroll
- [ ] Readable on small screens

---

## Functional Testing

### Consent Actions - Accept All

**Test:**
1. Clear localStorage
2. Load page
3. Click "Accept All" button

**Expected:**
- [ ] Popup disappears immediately
- [ ] localStorage contains: `{"analytics":true,"ads":true}`
- [ ] Console shows GCM update with all "granted"
- [ ] Page reload → Popup doesn't show again
- [ ] Consent remembered

**Verify in Console:**
```javascript
localStorage.getItem('mdcc_consent')
// Should return: {"analytics":true,"ads":true}

window.dataLayer
// Should show: consent update events with "granted"
```

### Consent Actions - Analytics Only

**Test:**
1. Clear localStorage
2. Load page
3. Click "Analytics Only" button

**Expected:**
- [ ] Popup disappears immediately
- [ ] localStorage contains: `{"analytics":true,"ads":false}`
- [ ] Console shows GCM: analytics granted, ads denied
- [ ] Page reload → Popup doesn't show again
- [ ] Consent remembered

**Verify in Console:**
```javascript
localStorage.getItem('mdcc_consent')
// Should return: {"analytics":true,"ads":false}
```

### Consent Actions - Decline All

**Test:**
1. Clear localStorage
2. Load page
3. Click "Decline All" button

**Expected:**
- [ ] Popup disappears immediately
- [ ] localStorage contains: `{"analytics":false,"ads":false}`
- [ ] Console shows GCM update with all "denied"
- [ ] Page reload → Popup shows again OR doesn't show (check settings)
- [ ] Consent remembered

### Keyboard Navigation

**Test:**
1. Load page with popup visible
2. Press Tab key repeatedly

**Expected:**
- [ ] Focus moves between buttons in logical order
- [ ] Visible focus outline on each button
- [ ] Can activate buttons with Enter/Space
- [ ] ESC key closes popup (if enabled)

**Accessibility:**
- [ ] `aria-hidden` attribute toggles correctly
- [ ] Focus management works (popup receives focus when shown)
- [ ] Screen reader announces popup correctly

### Shortcode Testing

**Test `[mdcc_consent_status]` Shortcode:**
1. Add shortcode to a page/post
2. Load page with different consent states

**Expected:**
- [ ] Shows "Granted" when analytics/ads consented
- [ ] Shows "Denied" when not consented
- [ ] Updates when consent changes

**Test `[mdcc_manage_consent]` Shortcode:**
1. Add shortcode to a page/post
2. Load page

**Expected:**
- [ ] Shows "Manage Cookie Consent" link
- [ ] Clicking link shows popup again
- [ ] Can change consent settings
- [ ] New settings save correctly

---

## Edge Cases

### localStorage Disabled/Blocked

**Test:**
1. Disable localStorage in browser
2. Load page
3. Try to consent

**Expected:**
- [ ] No JavaScript errors
- [ ] Graceful degradation (cookies fallback or session-only)
- [ ] Plugin doesn't break site

### Google Tag Manager Not Loaded

**Test:**
1. Ensure window.gtag is undefined
2. Load page
3. Consent to cookies

**Expected:**
- [ ] No JavaScript errors
- [ ] Consent still saves to localStorage
- [ ] Plugin works without GTM

### Multiple Rapid Clicks

**Test:**
1. Load page
2. Rapidly click "Accept All" 5 times quickly

**Expected:**
- [ ] No errors
- [ ] Only one consent saved
- [ ] Popup closes properly
- [ ] No duplicate events fired

### Browser Back/Forward

**Test:**
1. Accept cookies on page A
2. Navigate to page B
3. Click browser back button

**Expected:**
- [ ] Consent state maintained
- [ ] Popup doesn't show again
- [ ] No errors in console

---

## Admin Settings Testing

### Style Settings

**Test:**
1. Go to Settings → Cookie Consent
2. Change popup style (Minimal/Modern/Bold)
3. Save settings
4. Clear localStorage
5. Load frontend

**Expected:**
- [ ] New style applies correctly
- [ ] Styling matches selected preset
- [ ] No visual glitches

### Position Settings

**Test:**
1. Change position (Top/Bottom/Center)
2. Save settings
3. Clear localStorage
4. Load frontend

**Expected:**
- [ ] Popup appears in correct position
- [ ] Positioning is correct
- [ ] No visual glitches

### Animation Settings

**Test:**
1. Change animation (Slide/Fade/None)
2. Save settings
3. Clear localStorage
4. Load frontend

**Expected:**
- [ ] Selected animation plays
- [ ] Animation smooth and correct
- [ ] No animation when "None" selected

### Color Customization

**Test:**
1. Change primary color
2. Save settings
3. Load frontend

**Expected:**
- [ ] Buttons use new color
- [ ] Hover states work
- [ ] Color contrast maintained (WCAG AA)

---

## Browser Compatibility

Test in ALL major browsers:

### Google Chrome (Latest)
- [ ] Popup displays correctly
- [ ] All styles work
- [ ] All animations work
- [ ] Console has no errors
- [ ] localStorage works

### Mozilla Firefox (Latest)
- [ ] Popup displays correctly
- [ ] All styles work
- [ ] All animations work
- [ ] Console has no errors
- [ ] localStorage works

### Apple Safari (Latest)
- [ ] Popup displays correctly
- [ ] All styles work
- [ ] All animations work
- [ ] Console has no errors
- [ ] localStorage works

### Microsoft Edge (Latest)
- [ ] Popup displays correctly
- [ ] All styles work
- [ ] All animations work
- [ ] Console has no errors
- [ ] localStorage works

### Mobile Browsers
- [ ] Safari iOS (iPhone/iPad)
- [ ] Chrome Android
- [ ] Touch interactions work
- [ ] Responsive layout correct

---

## Accessibility Testing

### WCAG 2.1 AA Compliance

**Color Contrast:**
- [ ] Text on buttons: ≥4.5:1 contrast ratio
- [ ] Button text readable
- [ ] Link text readable
- [ ] Use WebAIM Contrast Checker

**Keyboard Navigation:**
- [ ] All interactive elements reachable via Tab
- [ ] Focus visible on all elements
- [ ] Logical tab order
- [ ] ESC key works (if enabled)

**Screen Reader:**
- [ ] NVDA (Windows) announces popup correctly
- [ ] VoiceOver (Mac) announces popup correctly
- [ ] Buttons have accessible labels
- [ ] `aria-hidden` works correctly

**Reduced Motion:**
- [ ] Enable "Reduce Motion" in OS settings
- [ ] Animations disabled automatically
- [ ] Popup still functions

**High Contrast Mode:**
- [ ] Enable High Contrast (Windows)
- [ ] Popup still visible
- [ ] Buttons distinguishable
- [ ] Text readable

---

## WordPress Integration

### Plugin Conflicts

**Test with popular plugins:**
- [ ] Yoast SEO → No conflicts
- [ ] Contact Form 7 → No conflicts
- [ ] WooCommerce → No conflicts
- [ ] Jetpack → No conflicts
- [ ] Other cookie plugins disabled

### Theme Compatibility

**Test with popular themes:**
- [ ] Twenty Twenty-Four → Works correctly
- [ ] Twenty Twenty-Three → Works correctly
- [ ] Astra → Works correctly
- [ ] GeneratePress → Works correctly
- [ ] Custom theme → Works correctly

### Multisite

If applicable:
- [ ] Network activate works
- [ ] Per-site settings work
- [ ] No cross-site issues

---

## Performance Benchmarks

### Page Load Impact

**Measure with GTmetrix/PageSpeed Insights:**

**Before plugin activation:**
- Load time: _____ ms
- Total page size: _____ KB

**After plugin activation:**
- Load time: _____ ms
- Total page size: _____ KB
- Impact: _____ ms (should be <30ms)

**Requirements:**
- [ ] Page load impact <30ms
- [ ] No render-blocking resources
- [ ] Assets load asynchronously

---

## Final Validation

### WordPress Plugin Check

**Run WordPress Plugin Check:**
```bash
wp plugin check maxtdesign-cookie-consent
```

**Expected:**
- [ ] 0 errors
- [ ] 0 warnings
- [ ] All checks pass

### PHP CodeSniffer

**Run PHPCS with WordPress standards:**
```bash
phpcs --standard=WordPress includes/
```

**Expected:**
- [ ] 0 errors
- [ ] 0 warnings (or only minor formatting)

### JavaScript Linting

**Run ESLint:**
```bash
npx eslint assets/js/
```

**Expected:**
- [ ] 0 errors
- [ ] 0 warnings (or only minor style issues)

---

## Test Results Summary

### Performance
- [ ] ✅ All performance targets met (<10KB)
- [ ] ✅ Page load impact acceptable (<30ms)
- [ ] ✅ Assets load correctly (minified in production)

### Visual
- [ ] ✅ All 9 style+position combinations correct
- [ ] ✅ All animations work smoothly
- [ ] ✅ Responsive at all breakpoints

### Functional
- [ ] ✅ All consent actions work correctly
- [ ] ✅ Google Consent Mode fires properly
- [ ] ✅ localStorage persistence works
- [ ] ✅ Shortcodes function correctly

### Compatibility
- [ ] ✅ All browsers tested and working
- [ ] ✅ No plugin conflicts
- [ ] ✅ Theme compatibility confirmed

### Accessibility
- [ ] ✅ WCAG 2.1 AA compliant
- [ ] ✅ Keyboard navigation works
- [ ] ✅ Screen reader compatible

### WordPress Standards
- [ ] ✅ Plugin Check passes
- [ ] ✅ PHPCS passes
- [ ] ✅ No errors or warnings

---

## Sign-off

**Tested by:** _____________________  
**Date:** _____________________  
**WordPress Version:** _____________________  
**PHP Version:** _____________________

**Ready for Production:** [ ] YES [ ] NO

**Notes:**
