<?php
/**
 * Build a WordPress.org-ready distribution zip for MaxtDesign Cookie Consent.
 *
 * Usage (Windows CLI PHP ships with zip disabled — the flag is required there):
 *   php -d extension=zip bin/build-zip.php [version]
 * Or via npm (runs the JS/CSS build first):
 *   npm run build:zip
 *
 * Method (matches maxtdesign-disable-rest-api / maxtdesign-product-bundles):
 *
 * - ALLOW-LIST is the primary gate. Only the files below ship — no dev file can
 *   leak even if one lands somewhere unexpected. .distignore is defense-in-depth
 *   (wp.org's own zip-builder reads it); this script also cross-checks every
 *   allow-listed file against .distignore so the two layers cannot drift.
 * - Entries are written with FORWARD SLASHES, slug-rooted. Compress-Archive /
 *   ZipFile::CreateFromDirectory on Windows PowerShell write backslash separators
 *   that Linux (wp.org / SVN) reads as literal-backslash FILENAMES, not folders,
 *   and the zip "extracts" into garbage.
 * - The slug comes from the Text Domain header (wp.org requires slug == text
 *   domain); it is NOT the folder name.
 * - Built min assets must be current. This plugin ships pre-built terser/postcss
 *   output, so the script refuses to package a .min that is older than its source
 *   — run `npm run build` first (npm run build:zip does this for you).
 *
 * This plugin is standalone: no composer, no vendored libs, so there is no
 * hard-rule-9 junction concern here (that check lives in the commerce-suite
 * plugins' build-zip).
 */

declare(strict_types=1);

if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "ZipArchive unavailable. Re-run: php -d extension=zip bin/build-zip.php\n" );
	exit( 1 );
}

$root = dirname( __DIR__ );

/* ---- headers ------------------------------------------------------------- */
$main = '';
foreach ( glob( $root . '/*.php' ) ?: array() as $file ) {
	$head = (string) file_get_contents( $file, false, null, 0, 8192 );
	if ( preg_match( '/^\s*\*\s*Plugin Name:/mi', $head ) ) {
		$main = $file;
		break;
	}
}
if ( '' === $main ) {
	fwrite( STDERR, "No main plugin file with a 'Plugin Name:' header.\n" );
	exit( 1 );
}
$head = (string) file_get_contents( $main, false, null, 0, 8192 );
$grab = static function ( string $label ) use ( $head ): string {
	return preg_match( '/^\s*\*\s*' . preg_quote( $label, '/' ) . ':\s*(.+?)\s*$/mi', $head, $m ) ? trim( $m[1] ) : '';
};
$slug    = $grab( 'Text Domain' );
$version = ( $argv[1] ?? '' ) !== '' ? trim( (string) $argv[1] ) : $grab( 'Version' );
$name    = $grab( 'Plugin Name' );
$mainRel = basename( $main );
if ( '' === $slug || '' === $version ) {
	fwrite( STDERR, "Missing Text Domain or Version header.\n" );
	exit( 1 );
}

/* ---- allow-list: exactly what ships to users ----------------------------- */
/* Globs auto-pick new files in these dirs (e.g. a new includes/*.php class)
 * while every top-level dev file and dir is never even considered. */
$patterns = array(
	$mainRel,
	'readme.txt',
	'LICENSE.txt',
	'uninstall.php',
	'includes/*.php',
	'assets/css/*.css',
	'assets/js/*.js',
	'languages/*.pot',
	'languages/README.md',
);

$files = array();
foreach ( $patterns as $pat ) {
	foreach ( glob( $root . '/' . $pat ) ?: array() as $abs ) {
		if ( is_file( $abs ) ) {
			$files[] = ltrim( str_replace( '\\', '/', substr( $abs, strlen( $root ) ) ), '/' );
		}
	}
}
$files = array_values( array_unique( $files ) );
sort( $files );
if ( ! $files ) {
	fwrite( STDERR, "Nothing matched the allow-list. Check the repo path.\n" );
	exit( 1 );
}

/* ---- staleness guard: every .min must be newer than its source ----------- */
$stale = array();
foreach ( $files as $rel ) {
	if ( ! preg_match( '/\.min\.(css|js)$/', $rel ) ) {
		continue;
	}
	$src = preg_replace( '/\.min\.(css|js)$/', '.$1', $rel );
	if ( in_array( $src, $files, true ) && filemtime( $root . '/' . $src ) > filemtime( $root . '/' . $rel ) ) {
		$stale[] = "$rel is older than $src";
	}
}
if ( $stale ) {
	fwrite( STDERR, "Stale built assets — run `npm run build` first:\n  " . implode( "\n  ", $stale ) . "\n" );
	exit( 1 );
}

/* ---- cross-check the allow-list against .distignore ---------------------- */
/* The two layers must agree: nothing we ship may match a distignore rule. */
$dist = array();
if ( is_readable( $root . '/.distignore' ) ) {
	foreach ( file( $root . '/.distignore', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) ?: array() as $line ) {
		$line = trim( $line );
		if ( '' === $line || str_starts_with( $line, '#' ) ) {
			continue;
		}
		$dist[] = $line;
	}
}
$dist_hits = static function ( string $rel ) use ( $dist ): bool {
	foreach ( $dist as $p ) {
		if ( str_starts_with( $p, '/' ) ) {
			$anchored = ltrim( $p, '/' );
			if ( $rel === $anchored || str_starts_with( $rel, $anchored . '/' ) ) {
				return true;
			}
		} elseif ( fnmatch( $p, basename( $rel ) ) ) {
			return true;
		}
	}
	return false;
};
$conflicts = array_filter( $files, $dist_hits );
if ( $conflicts ) {
	fwrite( STDERR, "Allow-list / .distignore conflict — these ship-files match a .distignore rule:\n  " . implode( "\n  ", $conflicts ) . "\n" );
	exit( 1 );
}

/* ---- PHP lint every shipped .php ----------------------------------------- */
foreach ( $files as $rel ) {
	if ( ! str_ends_with( $rel, '.php' ) ) {
		continue;
	}
	exec( 'php -l ' . escapeshellarg( $root . '/' . $rel ) . ' 2>&1', $out, $code );
	if ( 0 !== $code ) {
		fwrite( STDERR, "PHP lint failed: $rel\n" . implode( "\n", $out ) . "\n" );
		exit( 1 );
	}
	$out = array();
}

/* ---- pack: forward-slash, slug-rooted ------------------------------------ */
$outDir = $root . '/_build';
if ( ! is_dir( $outDir ) ) {
	mkdir( $outDir, 0777, true );
}
$zipPath = $outDir . '/' . $slug . '-' . $version . '.zip';
if ( file_exists( $zipPath ) ) {
	unlink( $zipPath );
}
$zip = new ZipArchive();
if ( true !== $zip->open( $zipPath, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Cannot create {$zipPath}\n" );
	exit( 1 );
}
foreach ( $files as $rel ) {
	$zip->addFile( $root . '/' . $rel, $slug . '/' . $rel );
}
$zip->close();

/* ---- verify: no backslash / dev entries leaked --------------------------- */
$check = new ZipArchive();
$check->open( $zipPath );
$entries = array();
for ( $i = 0; $i < $check->numFiles; $i++ ) {
	$entries[] = $check->getNameIndex( $i );
}
$check->close();
$bad = array_filter(
	$entries,
	static function ( string $e ): bool {
		return (bool) preg_match( '#\\\\|(^|/)\.(git|claude|distignore)|(^|/)(tools|bin|tests|docs|node_modules)/|(^|/)(package\.json|phpstan)#', $e );
	}
);
if ( $bad ) {
	fwrite( STDERR, "Dev/invalid entries leaked into the zip:\n  " . implode( "\n  ", $bad ) . "\n" );
	exit( 1 );
}

/* ---- report -------------------------------------------------------------- */
printf( "Plugin : %s\n", $name );
printf( "Slug   : %s  (from Text Domain; wp.org requires slug == text domain)\n", $slug );
printf( "Version: %s\n", $version );
printf( "Zip    : %s (%s KB)\n", $zipPath, number_format( filesize( $zipPath ) / 1024, 1 ) );
printf( "Files  : %d\n", count( $entries ) );
foreach ( $entries as $e ) {
	echo "  $e\n";
}
echo "\nTest this zip on a clean install before uploading to WordPress.org.\n";
