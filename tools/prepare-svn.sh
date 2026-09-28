#!/bin/bash
#
# Prepares plugin files for WordPress.org SVN upload (trunk/tag).
# Runs build, then copies only SVN-allowed files into svn-upload/trunk/.
# Usage: ./tools/prepare-svn.sh [version]
#
# The file list comes from `php bin/build-zip.php --list`, the same allow-list
# the distribution zip is built from. Do not add hand-written cp lines here:
# a hand-written list left popup-loader.js out of the 1.11.0 candidate.
#
# Environment (used by tests/packaging-test.php):
#   MDCC_SVN_OUT     output directory, default svn-upload/trunk
#   MDCC_SKIP_BUILD  set to 1 to stage the assets as they are
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

VERSION="${1:-$(node -p "require('./package.json').version" 2>/dev/null || echo "dev")}"
OUT_DIR="${MDCC_SVN_OUT:-svn-upload/trunk}"

echo "=============================================="
echo "MaxtDesign Cookie Consent - Prepare for SVN"
echo "=============================================="
echo "Version: $VERSION"
echo "Output:  $OUT_DIR/"
echo ""

if [ "${MDCC_SKIP_BUILD:-0}" != "1" ]; then
  if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
  fi
  npm run build
fi

# Fails here, before anything is staged, on a stale .min file, a .distignore
# conflict or a PHP lint error.
FILES="$(php bin/build-zip.php --list | tr -d '\r')"

if [ -z "$FILES" ]; then
  echo "bin/build-zip.php --list returned no files." >&2
  exit 1
fi

# The output folder is deleted first. Refuse anything that is not a folder
# named "trunk".
case "$OUT_DIR" in
  */trunk) ;;
  *) echo "Refusing to stage into '$OUT_DIR': the path must end in /trunk." >&2; exit 1 ;;
esac

rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR"

COUNT=0
while IFS= read -r FILE; do
  [ -n "$FILE" ] || continue
  mkdir -p "$OUT_DIR/$(dirname "$FILE")"
  cp "$FILE" "$OUT_DIR/$FILE"
  COUNT=$((COUNT + 1))
done <<< "$FILES"

# The popup is loaded by this file. A trunk without it ships a popup that
# never appears.
for REQUIRED in assets/js/popup-loader.js assets/js/popup-loader.min.js; do
  if [ ! -s "$OUT_DIR/$REQUIRED" ]; then
    echo "Missing from the staged trunk: $REQUIRED" >&2
    exit 1
  fi
done

echo "Staged $COUNT files."
echo "Done. Copy to SVN trunk then: svn ci -m \"Update to $VERSION\"; svn cp trunk tags/$VERSION; svn ci -m \"Tagging $VERSION\""
