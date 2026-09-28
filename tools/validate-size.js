/**
 * Size Validation Tool
 * Validates that minified assets meet size targets
 *
 * Usage: node tools/validate-size.js
 */

const fs = require('fs');
const path = require('path');

const FILES = {
  'popup.min.css': 'assets/css/popup.min.css',
  'consent-runtime.min.js': 'assets/js/consent-runtime.min.js',
  'popup.min.js': 'assets/js/popup.min.js',
  'popup-loader.min.js': 'assets/js/popup-loader.min.js'
};

const INDIVIDUAL_TARGET = 5120;
// Retain the original runtime+CSS cap, and count the previously omitted popup
// behavior and the new lazy loader in a separate full-dialog budget.
const TOTAL_TARGET = 14336;
const coreSize = ['popup.min.css', 'consent-runtime.min.js'].reduce((n, f) => n + fs.statSync(path.join(__dirname, '..', FILES[f])).size, 0);
if (coreSize > 10240 || fs.statSync(path.join(__dirname, '..', FILES['popup-loader.min.js'])).size >= 1024) {
  throw new Error('Original 10KB core budget or under-1KB bootstrap budget exceeded');
}

// The design settings print one inline <style> block on sites that use them.
// Cap: under 512 B with every field set. A default site must print nothing.
const INLINE_DESIGN_CAP = 512;
const renderInline = (settings) => JSON.parse(require('child_process').execFileSync(
  'php',
  [path.join(__dirname, '..', 'tests', 'popup-render.php'), JSON.stringify(settings)],
  { encoding: 'utf8' }
)).inline_css;
const inlineDefault = Buffer.byteLength(renderInline({}));
const inlineFull = Buffer.byteLength(renderInline({
  popup_primary_color: '#c8102e',
  popup_bg_color: '#111111',
  popup_text_color: '#ffffff',
  popup_button_text_color: '#000000',
  popup_radius: '24',
  popup_desktop_width: '60',
  popup_inherit_font: true,
  popup_buttons: 'compact',
  manage_url: '/cookie-settings/'
}));
if (inlineDefault !== 0 || inlineFull <= 0 || inlineFull >= INLINE_DESIGN_CAP) {
  throw new Error('Inline design block budget failed: default site ' + inlineDefault + ' B (must be 0), every field set ' + inlineFull + ' B (must be under ' + INLINE_DESIGN_CAP + ')');
}

const RED = '\x1b[31m';
const GREEN = '\x1b[32m';
const YELLOW = '\x1b[33m';
const RESET = '\x1b[0m';

console.log('==========================================');
console.log('Size Validation');
console.log('==========================================\n');

let totalSize = 0;
let allPassed = true;

Object.entries(FILES).forEach(([name, filepath]) => {
  const fullPath = path.join(__dirname, '..', filepath);

  if (!fs.existsSync(fullPath)) {
    console.log(`${RED}${name}: File not found${RESET}`);
    allPassed = false;
    return;
  }

  const size = fs.statSync(fullPath).size;
  totalSize += size;

  const sizeKB = (size / 1024).toFixed(2);
  const targetKB = (INDIVIDUAL_TARGET / 1024).toFixed(2);

  if (size <= INDIVIDUAL_TARGET) {
    console.log(`${GREEN}OK ${name}: ${size} bytes (${sizeKB}KB) - Under ${targetKB}KB target${RESET}`);
  } else {
    const over = size - INDIVIDUAL_TARGET;
    console.log(`${YELLOW}WARN ${name}: ${size} bytes (${sizeKB}KB) - Over by ${over} bytes${RESET}`);
  }
});

console.log(`${GREEN}OK core (popup.min.css + consent-runtime.min.js): ${coreSize} bytes - cap 10240${RESET}`);
console.log(`${GREEN}OK inline design block: default site ${inlineDefault} bytes, every field set ${inlineFull} bytes - cap under ${INLINE_DESIGN_CAP}${RESET}`);

console.log('\n==========================================');

const totalKB = (totalSize / 1024).toFixed(2);
const targetKB = (TOTAL_TARGET / 1024).toFixed(2);

console.log(`Total: ${totalSize} bytes (${totalKB}KB)`);
console.log(`Target: ${TOTAL_TARGET} bytes (${targetKB}KB)`);

if (totalSize <= TOTAL_TARGET) {
  const under = TOTAL_TARGET - totalSize;
  console.log(`${GREEN}SUCCESS - Under target by ${under} bytes${RESET}\n`);
} else {
  const over = totalSize - TOTAL_TARGET;
  console.log(`${RED}FAILED - Over target by ${over} bytes${RESET}\n`);
  allPassed = false;
}

process.exit(allPassed ? 0 : 1);
