#!/bin/bash
#
# Build script for MaxtDesign Cookie Consent
# Generates minified CSS and JS files
#
# Usage: ./tools/build.sh
#

set -e

echo "=================================="
echo "MaxtDesign Cookie Consent - Build"
echo "=================================="
echo ""

# Check if npm is available
if ! command -v npm &> /dev/null; then
    echo "ERROR: npm not found. Install Node.js first."
    exit 1
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
    echo ""
fi

# Build frontend assets
echo "Building frontend assets..."
npm run build
echo ""

# Build admin assets
echo "Building admin assets..."
npm run build:admin-css
npm run build:admin-js
echo ""

# Calculate sizes
echo "=================================="
echo "Asset Sizes"
echo "=================================="
echo ""

CSS_MIN=0
JS_MIN=0

# Frontend CSS
if [ -f "assets/css/popup.css" ] && [ -f "assets/css/popup.min.css" ]; then
    CSS_ORIG=$(stat -f%z "assets/css/popup.css" 2>/dev/null || stat -c%s "assets/css/popup.css")
    CSS_MIN=$(stat -f%z "assets/css/popup.min.css" 2>/dev/null || stat -c%s "assets/css/popup.min.css")
    CSS_REDUCTION=$(echo "scale=1; ($CSS_ORIG - $CSS_MIN) / $CSS_ORIG * 100" | bc 2>/dev/null || echo "N/A")
    echo "popup.css:"
    echo "  Source:   $CSS_ORIG bytes"
    echo "  Minified: $CSS_MIN bytes"
    echo "  Saved:    ${CSS_REDUCTION}%"
    echo ""
fi

# Frontend JS
if [ -f "assets/js/consent-runtime.js" ] && [ -f "assets/js/consent-runtime.min.js" ]; then
    JS_ORIG=$(stat -f%z "assets/js/consent-runtime.js" 2>/dev/null || stat -c%s "assets/js/consent-runtime.js")
    JS_MIN=$(stat -f%z "assets/js/consent-runtime.min.js" 2>/dev/null || stat -c%s "assets/js/consent-runtime.min.js")
    JS_REDUCTION=$(echo "scale=1; ($JS_ORIG - $JS_MIN) / $JS_ORIG * 100" | bc 2>/dev/null || echo "N/A")
    echo "consent-runtime.js:"
    echo "  Source:   $JS_ORIG bytes"
    echo "  Minified: $JS_MIN bytes"
    echo "  Saved:    ${JS_REDUCTION}%"
    echo ""
fi

# Total frontend size
TARGET=10240
TOTAL_MIN=$((CSS_MIN + JS_MIN))

echo "=================================="
echo "Frontend Total (minified): $TOTAL_MIN bytes"
echo "Target: 10KB (10,240 bytes)"
echo ""

if [ $TOTAL_MIN -le $TARGET ]; then
    echo "SUCCESS - Under target by $(($TARGET - $TOTAL_MIN)) bytes"
else
    echo "Over target by $(($TOTAL_MIN - $TARGET)) bytes"
fi
echo ""

# Run validation
echo "Running size validation..."
npm run validate

echo ""
echo "Build complete!"
