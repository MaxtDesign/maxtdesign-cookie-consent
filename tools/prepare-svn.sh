#!/bin/bash
#
# Prepares plugin files for WordPress.org SVN upload (trunk/tag).
# Runs build, then copies only SVN-allowed files into svn-upload/trunk/.
# Usage: ./tools/prepare-svn.sh [version]
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

VERSION="${1:-$(node -p "require('./package.json').version" 2>/dev/null || echo "dev")}"
OUT_DIR="svn-upload/trunk"

echo "=============================================="
echo "MaxtDesign Cookie Consent - Prepare for SVN"
echo "=============================================="
echo "Version: $VERSION"
echo "Output:  $OUT_DIR/"
echo ""

if [ -d "node_modules" ]; then
  npm run build
else
  echo "Installing dependencies..."
  npm install
  npm run build
fi

rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR/includes" "$OUT_DIR/assets/css" "$OUT_DIR/assets/js" "$OUT_DIR/languages"

cp maxtdesign-cookie-consent.php readme.txt LICENSE.txt uninstall.php "$OUT_DIR/"
cp includes/class-admin-settings.php includes/class-consent-manager.php includes/class-popup-system.php includes/class-shortcodes.php "$OUT_DIR/includes/"
cp assets/css/admin.css assets/css/admin.min.css assets/css/popup.css assets/css/popup.min.css "$OUT_DIR/assets/css/"
cp assets/js/admin.js assets/js/admin.min.js assets/js/consent-runtime.js assets/js/consent-runtime.min.js assets/js/popup.js assets/js/popup.min.js "$OUT_DIR/assets/js/"
cp languages/maxtdesign-cookie-consent.pot languages/README.md "$OUT_DIR/languages/"

echo "Done. Copy to SVN trunk then: svn ci -m \"Update to $VERSION\"; svn cp trunk tags/$VERSION; svn ci -m \"Tagging $VERSION\""
