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
  'consent-runtime.min.js': 'assets/js/consent-runtime.min.js'
};

const INDIVIDUAL_TARGET = 5120;
const TOTAL_TARGET = 10240;

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
