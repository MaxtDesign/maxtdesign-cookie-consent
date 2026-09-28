<?php
/**
 * Packaging test
 *
 * Builds the distribution zip, extracts it, and checks what a user would
 * actually install. Then stages the SVN trunk with tools/prepare-svn.sh and
 * checks that it ships the same files with the same content.
 *
 * Run: php -d extension=zip tests/packaging-test.php
 *
 * A working-tree check cannot prove a zip, so every assertion here reads the
 * extracted artifact. Dev tooling only, never shipped.
 *
 * @package MaxtDesign_Cookie_Consent
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "ZipArchive unavailable. Re-run: php -d extension=zip tests/packaging-test.php\n" );
	exit( 1 );
}

$root   = str_replace( '\\', '/', dirname( __DIR__ ) );
$slug   = 'maxtdesign-cookie-consent';
$passed = 0;
$failed = 0;

/**
 * Record one check.
 *
 * @param string $description What is being checked.
 * @param bool   $condition   True when it holds.
 * @param string $detail      Shown on failure.
 */
function mdcc_check( $description, $condition, $detail = '' ) {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		echo "PASS: {$description}\n";
		return;
	}
	++$failed;
	echo "FAIL: {$description}" . ( '' !== $detail ? "\n      {$detail}" : '' ) . "\n";
}

/**
 * Relative paths of every file under a directory, sorted.
 *
 * @param string $dir Directory to walk.
 * @return string[]
 */
function mdcc_files_in( $dir ) {
	$found = array();
	if ( ! is_dir( $dir ) ) {
		return $found;
	}
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		if ( $file->isFile() ) {
			$found[] = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) ) ), '/' );
		}
	}
	sort( $found );
	return $found;
}

/**
 * Remove a directory tree created by this test.
 *
 * @param string $dir Directory to remove.
 */
function mdcc_remove_tree( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $iterator as $file ) {
		$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
	}
	rmdir( $dir );
}

$work = str_replace( '\\', '/', sys_get_temp_dir() ) . '/mdcc-packaging-' . getmypid();
mdcc_remove_tree( $work );
mkdir( $work . '/zip', 0777, true );

/* ---- 1. build the zip ------------------------------------------------------ */

$head    = (string) file_get_contents( $root . '/' . $slug . '.php', false, null, 0, 8192 );
$version = preg_match( '/^\s*\*\s*Version:\s*(.+?)\s*$/mi', $head, $m ) ? $m[1] : '';
$zip_path = $root . '/_build/' . $slug . '-' . $version . '.zip';

exec( 'php -d extension=zip ' . escapeshellarg( $root . '/bin/build-zip.php' ) . ' 2>&1', $build_out, $build_code );
mdcc_check( 'bin/build-zip.php exits 0', 0 === $build_code, implode( ' | ', array_slice( $build_out, -3 ) ) );
mdcc_check( 'zip exists at _build/' . basename( $zip_path ), is_file( $zip_path ) );

if ( ! is_file( $zip_path ) ) {
	echo "\nPackaging: {$passed} passed, " . ( $failed ) . " failed\n";
	exit( 1 );
}

/* ---- 2. extract and inspect ------------------------------------------------ */

$zip = new ZipArchive();
$zip->open( $zip_path );
$zip->extractTo( $work . '/zip' );
$zip->close();

$extracted = $work . '/zip/' . $slug;
$in_zip    = mdcc_files_in( $extracted );

mdcc_check( 'zip extracts into one ' . $slug . '/ folder', is_dir( $extracted ) && array( $slug ) === array_values( array_diff( (array) scandir( $work . '/zip' ), array( '.', '..' ) ) ) );

// Files the running plugin reads or serves. Missing any one breaks a site.
$required = array(
	$slug . '.php',
	'readme.txt',
	'uninstall.php',
	'includes/class-popup-system.php',
	'includes/class-consent-manager.php',
	'assets/css/popup.css',
	'assets/css/popup.min.css',
	'assets/js/consent-runtime.js',
	'assets/js/consent-runtime.min.js',
	'assets/js/popup.js',
	'assets/js/popup.min.js',
	'assets/js/popup-loader.js',
	'assets/js/popup-loader.min.js',
);
foreach ( glob( $root . '/templates/*.php' ) ?: array() as $template ) {
	$required[] = 'templates/' . basename( $template );
}
foreach ( $required as $rel ) {
	mdcc_check( "zip contains {$rel}", in_array( $rel, $in_zip, true ) && filesize( $extracted . '/' . $rel ) > 0 );
}

// Boot the extracted copy, not the working tree, and render the popup from it.
putenv( 'MDCC_RENDER_ROOT=' . $extracted );
exec( 'php ' . escapeshellarg( $root . '/tests/popup-render.php' ) . ' ' . escapeshellarg( '{}' ) . ' 2>&1', $boot_out, $boot_code );
putenv( 'MDCC_RENDER_ROOT' );
$boot = json_decode( implode( "\n", $boot_out ), true );
mdcc_check( 'extracted plugin boots and renders the popup', 0 === $boot_code && is_array( $boot ) && false !== strpos( (string) $boot['html'], 'mdcc-popup__actions' ), implode( ' | ', array_slice( $boot_out, -2 ) ) );
mdcc_check(
	'extracted plugin prints the inline loader (' . ( is_array( $boot ) ? (int) $boot['inline_js_bytes'] : 0 ) . ' B, under 1024)',
	is_array( $boot ) && $boot['inline_js_bytes'] > 0 && $boot['inline_js_bytes'] < 1024
);

$leaked = preg_grep( '#(^|/)(\.claude|\.git|tests|tools|bin|docs|node_modules|stubs|svn-upload|_build)(/|$)|package\.json|phpstan#', $in_zip );
mdcc_check( 'zip contains no dev files', ! $leaked, implode( ', ', (array) $leaked ) );

$changed = array();
foreach ( $in_zip as $rel ) {
	if ( ! is_file( $root . '/' . $rel ) || hash_file( 'sha256', $root . '/' . $rel ) !== hash_file( 'sha256', $extracted . '/' . $rel ) ) {
		$changed[] = $rel;
	}
}
mdcc_check( 'every extracted file matches its source (sha256)', ! $changed, implode( ', ', $changed ) );

/* ---- 3. stage the SVN trunk and compare ------------------------------------ */

$trunk = $work . '/trunk';
putenv( 'MDCC_SVN_OUT=' . $trunk );
putenv( 'MDCC_SKIP_BUILD=1' );
exec( 'bash ' . escapeshellarg( $root . '/tools/prepare-svn.sh' ) . ' 2>&1', $svn_out, $svn_code );
mdcc_check( 'tools/prepare-svn.sh exits 0', 0 === $svn_code, implode( ' | ', array_slice( $svn_out, -3 ) ) );

$in_trunk = mdcc_files_in( $trunk );
mdcc_check(
	'staged trunk and zip hold the same ' . count( $in_zip ) . ' files',
	$in_trunk === $in_zip,
	'only in zip: ' . implode( ', ', array_diff( $in_zip, $in_trunk ) ) . ' / only in trunk: ' . implode( ', ', array_diff( $in_trunk, $in_zip ) )
);

$differs = array();
foreach ( array_intersect( $in_zip, $in_trunk ) as $rel ) {
	if ( hash_file( 'sha256', $trunk . '/' . $rel ) !== hash_file( 'sha256', $extracted . '/' . $rel ) ) {
		$differs[] = $rel;
	}
}
mdcc_check( 'staged trunk files match the zip (sha256)', ! $differs, implode( ', ', $differs ) );

/* ---- done ------------------------------------------------------------------ */

mdcc_remove_tree( $work );

echo "\nPackaging: {$passed} passed, {$failed} failed, " . count( $in_zip ) . " files in the zip\n";
exit( 0 === $failed ? 0 : 1 );
