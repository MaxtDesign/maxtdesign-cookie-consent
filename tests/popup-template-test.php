<?php
/**
 * Popup template override test
 *
 * Checks the template lookup order (child theme, parent theme, bundled), the
 * mdcc_popup_template filter, and the @version reader, by running
 * tests/popup-render.php against throwaway theme folders.
 *
 * Run: php tests/popup-template-test.php
 *
 * @package MaxtDesign_Cookie_Consent
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root   = str_replace( '\\', '/', dirname( __DIR__ ) );
$work   = str_replace( '\\', '/', sys_get_temp_dir() ) . '/mdcc-template-' . getmypid();
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
 * Render the popup with the given environment.
 *
 * @param array<string, string> $env Environment variables for the harness.
 * @return array<string, mixed>
 */
function mdcc_render( $env ) {
	global $root;
	foreach ( array( 'MDCC_THEME_DIRS', 'MDCC_TEMPLATE_FILTER' ) as $name ) {
		putenv( isset( $env[ $name ] ) ? $name . '=' . $env[ $name ] : $name );
	}
	exec( 'php ' . escapeshellarg( $root . '/tests/popup-render.php' ) . ' ' . escapeshellarg( '{}' ) . ' 2>&1', $out, $code );
	$data = json_decode( implode( "\n", $out ), true );
	return is_array( $data ) ? $data : array( 'html' => '', 'template' => '', 'error' => implode( ' | ', $out ) );
}

/**
 * Write a minimal override that keeps the contract and carries a marker.
 *
 * @param string $dir     Theme folder.
 * @param string $marker  Text to find in the output.
 * @param string $version Value for the version tag, or '' for none.
 */
function mdcc_write_override( $dir, $marker, $version ) {
	mkdir( $dir . '/maxtdesign-cookie-consent', 0777, true );
	$tag = '' !== $version ? " * @version {$version}\n" : '';
	file_put_contents(
		$dir . '/maxtdesign-cookie-consent/popup.php',
		"<?php\n/**\n * Override.\n *\n{$tag} */\n?>\n"
		. '<div class="<?php echo esc_attr( implode( \' \', $args[\'classes\'] ) ); ?>" style="display: none;" data-marker="' . $marker . '">'
		. '<h2 id="mdcc-popup-title"><?php echo esc_html( $args[\'title\'] ); ?></h2>'
		. '<p id="mdcc-popup-message"><?php echo esc_html( $args[\'message\'] ); ?></p>'
		. '<button class="mdcc-popup__close"></button>'
		. '<button data-mdcc-action="accept-all"><?php echo esc_html( $args[\'labels\'][\'accept\'] ); ?></button>'
		. '<button data-mdcc-action="analytics-only"></button><button data-mdcc-action="decline-all"></button>'
		. "</div>\n"
	);
}

$child  = $work . '/child';
$parent = $work . '/parent';
$other  = $work . '/elsewhere';
mdcc_write_override( $child, 'child', '1.11.0' );
mdcc_write_override( $parent, 'parent', '1.0.0' );
mdcc_write_override( $other, 'filtered', '' );

$bundled = $root . '/templates/popup.php';

$r = mdcc_render( array() );
mdcc_check( 'no theme override: the bundled template is used', $r['template'] === $bundled, (string) $r['template'] );
mdcc_check( 'bundled output has the three actions', 3 === substr_count( (string) $r['html'], 'data-mdcc-action=' ) );

$r = mdcc_render( array( 'MDCC_THEME_DIRS' => $parent ) );
mdcc_check( 'parent theme override is used', false !== strpos( (string) $r['html'], 'data-marker="parent"' ) );
mdcc_check( 'override receives the title and labels', false !== strpos( (string) $r['html'], '>Cookie Consent</h2>' ) && false !== strpos( (string) $r['html'], '>Accept All</button>' ) );

$r = mdcc_render( array( 'MDCC_THEME_DIRS' => $child . '|' . $parent ) );
mdcc_check( 'child theme override wins over the parent', false !== strpos( (string) $r['html'], 'data-marker="child"' ) );

$r = mdcc_render( array( 'MDCC_THEME_DIRS' => $child, 'MDCC_TEMPLATE_FILTER' => $other . '/maxtdesign-cookie-consent/popup.php' ) );
mdcc_check( 'mdcc_popup_template filter wins over the theme', false !== strpos( (string) $r['html'], 'data-marker="filtered"' ) );

$r = mdcc_render( array( 'MDCC_TEMPLATE_FILTER' => $work . '/does-not-exist.php' ) );
mdcc_check( 'a filtered path that cannot be read falls back to the bundled template', $r['template'] === $bundled && 3 === substr_count( (string) $r['html'], 'data-mdcc-action=' ), (string) $r['template'] );

/* ---- @version reader, loaded through the harness stubs --------------------- */

putenv( 'MDCC_THEME_DIRS' );
putenv( 'MDCC_TEMPLATE_FILTER' );
$probe = $work . '/probe.php';
file_put_contents(
	$probe,
	'<?php $argv = array( "probe", "{}" ); ob_start(); require ' . var_export( $root . '/tests/popup-render.php', true ) . '; ob_end_clean();'
	. 'echo json_encode( array('
	. 'MDCC_Popup_System::template_version( ' . var_export( $bundled, true ) . ' ),'
	. 'MDCC_Popup_System::template_version( ' . var_export( $parent . '/maxtdesign-cookie-consent/popup.php', true ) . ' ),'
	. 'MDCC_Popup_System::template_version( ' . var_export( $other . '/maxtdesign-cookie-consent/popup.php', true ) . ' ),'
	. 'MDCC_Popup_System::template_version( ' . var_export( $work . '/missing.php', true ) . ' )'
	. ') );'
);
exec( 'php ' . escapeshellarg( $probe ) . ' 2>&1', $probe_out, $probe_code );
$versions = json_decode( (string) end( $probe_out ), true );
$head     = (string) file_get_contents( $root . '/maxtdesign-cookie-consent.php', false, null, 0, 8192 );
preg_match( '/^\s*\*\s*Version:\s*(.+?)\s*$/mi', $head, $m );

mdcc_check( 'bundled template carries a version', is_array( $versions ) && 1 === preg_match( '/^\d+\.\d+\.\d+$/', (string) $versions[0] ), implode( ' | ', $probe_out ) );
mdcc_check( 'bundled template version is not newer than the plugin (' . ( $versions[0] ?? '?' ) . ' vs ' . ( $m[1] ?? '?' ) . ')', is_array( $versions ) && version_compare( (string) $versions[0], (string) ( $m[1] ?? '0' ), '<=' ) );
mdcc_check( 'override version is read', is_array( $versions ) && '1.0.0' === $versions[1] );
mdcc_check( 'override without a version reads as empty', is_array( $versions ) && '' === $versions[2] );
mdcc_check( 'missing file reads as empty', is_array( $versions ) && '' === $versions[3] );

/* ---- the bundled template keeps its own contract --------------------------- */

$template = (string) file_get_contents( $bundled );
foreach ( array( 'class="<?php echo esc_attr(implode', 'id="mdcc-popup-title"', 'id="mdcc-popup-message"', 'data-mdcc-action="accept-all"', 'data-mdcc-action="decline-all"', 'data-mdcc-action="analytics-only"', 'class="mdcc-popup__close"', "do_action('mdcc_popup_before_actions'" ) as $needle ) {
	mdcc_check( "bundled template keeps {$needle}", false !== strpos( $template, $needle ) );
}

/* ---- clean up --------------------------------------------------------------- */

$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $work, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
foreach ( $iterator as $file ) {
	$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
}
rmdir( $work );

echo "\nTemplate: {$passed} passed, {$failed} failed\n";
exit( 0 === $failed ? 0 : 1 );
