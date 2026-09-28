<?php
/**
 * Popup design settings test
 *
 * Checks what MDCC_Admin_Settings::sanitize_settings() stores for the design
 * settings, how the cookie settings page is resolved and kept current, and
 * when the Compact layout falls back to Standard. Runs the real classes
 * behind the WordPress stubs of tests/popup-render.php.
 *
 * Run: php tests/popup-settings-test.php
 *
 * @package MaxtDesign_Cookie_Consent
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

// Load the plugin through the render harness and discard what it prints.
$argv = array( 'popup-settings-test', '{}' );
ob_start();
require __DIR__ . '/popup-render.php';
ob_end_clean();

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . $path;
}

$passed = 0;
$failed = 0;

/**
 * Record one check.
 *
 * @param string $description What is being checked.
 * @param bool   $condition   True when it holds.
 * @param mixed  $detail      Shown on failure.
 */
function mdcc_check( $description, $condition, $detail = null ) {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		return;
	}
	++$failed;
	echo "FAIL: {$description}" . ( null !== $detail ? "\n      got: " . json_encode( $detail ) : '' ) . "\n";
}

$GLOBALS['mdcc_test']['pages'] = array(
	12 => array( 'page', 'publish', 'https://example.test/cookie-settings/' ),
	13 => array( 'page', 'draft', 'https://example.test/?page_id=13' ),
	14 => array( 'post', 'publish', 'https://example.test/a-post/' ),
	15 => array( 'page', 'publish', 'https://other.test/cookies/' ),
);

$admin = MDCC_Admin_Settings::get_instance();
$base  = array(
	'popup_style'     => 'minimal',
	'popup_position'  => 'bottom',
	'popup_animation' => 'slide',
);
$save  = function ( $input ) use ( $admin, $base ) {
	return $admin->sanitize_settings( array_merge( $base, $input ) );
};

/* ---- a form saved without touching the new fields ------------------------- */

$s = $save( array() );
mdcc_check( 'untouched form: buttons standard', 'standard' === $s['popup_buttons'], $s['popup_buttons'] );
mdcc_check( 'untouched form: no page, no address', 0 === $s['manage_page_id'] && '' === $s['manage_url'], $s );
mdcc_check( 'untouched form: width 100', 100 === $s['popup_desktop_width'], $s['popup_desktop_width'] );
mdcc_check( 'untouched form: colours, radius and labels empty', '' === $s['popup_bg_color'] . $s['popup_text_color'] . $s['popup_button_text_color'] . $s['popup_radius'] . $s['label_accept'] . $s['label_manage'] . $s['label_decline'] . $s['label_analytics'] );
mdcc_check( 'untouched form: font off', false === $s['popup_inherit_font'] );
mdcc_check( 'untouched form prints no inline style', '' === MDCC_Popup_System::get_design_css( $s ), MDCC_Popup_System::get_design_css( $s ) );
mdcc_check( 'every key of the defaults is saved', array() === array_diff( array_keys( mdcc_default_settings() ), array_keys( $s ) ), array_diff( array_keys( mdcc_default_settings() ), array_keys( $s ) ) );

/* ---- cookie settings page -------------------------------------------------- */

$s = $save( array( 'popup_buttons' => 'compact', 'manage_page_id' => '12' ) );
mdcc_check( 'published page: id kept', 12 === $s['manage_page_id'], $s['manage_page_id'] );
mdcc_check( 'published page: address stored relative to the home URL', '/cookie-settings/' === $s['manage_url'], $s['manage_url'] );
mdcc_check( 'published page: layout is compact', 'compact' === MDCC_Popup_System::get_button_layout( $s ) );
mdcc_check( 'published page: full address at render', 'https://example.test/cookie-settings/' === MDCC_Popup_System::get_manage_url( $s ), MDCC_Popup_System::get_manage_url( $s ) );

foreach ( array( 13 => 'draft page', 14 => 'post, not a page', 99 => 'missing page', 0 => 'no page', 'abc' => 'text instead of an id' ) as $id => $what ) {
	$s = $save( array( 'popup_buttons' => 'compact', 'manage_page_id' => $id ) );
	mdcc_check( "{$what}: nothing stored", 0 === $s['manage_page_id'] && '' === $s['manage_url'], $s );
	mdcc_check( "{$what}: layout falls back to standard", 'standard' === MDCC_Popup_System::get_button_layout( $s ) );
}

$s = $save( array( 'manage_page_id' => 12, 'manage_url' => 'https://evil.test/' ) );
mdcc_check( 'an address sent with the form is ignored', '/cookie-settings/' === $s['manage_url'], $s['manage_url'] );

$s = $save( array( 'manage_page_id' => 15 ) );
mdcc_check( 'page outside the home URL: full address stored', 'https://other.test/cookies/' === $s['manage_url'], $s['manage_url'] );

foreach ( array( 'javascript:alert(1)', '//evil.test/x', 'data:text/html,x', 'https://a.test/"><script>', array( 'x' ) ) as $bad ) {
	$tampered = array( 'popup_buttons' => 'compact', 'manage_url' => $bad );
	mdcc_check( 'tampered address ' . json_encode( $bad ) . ' counts as no page', '' === MDCC_Popup_System::get_manage_url( $tampered ) && 'standard' === MDCC_Popup_System::get_button_layout( $tampered ), MDCC_Popup_System::get_manage_url( $tampered ) );
}

/* ---- the saved address follows the page ------------------------------------ */

$popup = MDCC_Popup_System::get_instance();
$GLOBALS['mdcc_test']['options']['mdcc_settings'] = array_merge( mdcc_default_settings(), array( 'popup_buttons' => 'compact', 'manage_page_id' => 12, 'manage_url' => '/cookie-settings/' ) );

$GLOBALS['mdcc_test']['pages'][12][2] = 'https://example.test/cookies/';
$popup->refresh_manage_url_for_post( 5 );
mdcc_check( 'another post changed: address untouched', '/cookie-settings/' === get_option( 'mdcc_settings' )['manage_url'] );
$popup->refresh_manage_url_for_post( 12 );
mdcc_check( 'page slug changed: address follows', '/cookies/' === get_option( 'mdcc_settings' )['manage_url'], get_option( 'mdcc_settings' )['manage_url'] );

$GLOBALS['mdcc_test']['pages'][12][1] = 'trash';
$popup->refresh_manage_url_on_status( 'trash', 'publish', (object) array( 'ID' => 12 ) );
$now = get_option( 'mdcc_settings' );
mdcc_check( 'page trashed: address cleared', '' === $now['manage_url'], $now['manage_url'] );
mdcc_check( 'page trashed: layout falls back to standard', 'standard' === MDCC_Popup_System::get_button_layout( $now ) );
mdcc_check( 'page trashed: the selection is kept for when it returns', 12 === $now['manage_page_id'] );

$GLOBALS['mdcc_test']['pages'][12][1] = 'publish';
$popup->refresh_manage_url_on_status( 'publish', 'trash', (object) array( 'ID' => 12 ) );
mdcc_check( 'page restored: compact again', 'compact' === MDCC_Popup_System::get_button_layout( get_option( 'mdcc_settings' ) ) );

$GLOBALS['mdcc_test']['pages'][12][2] = 'https://example.test/privacy/cookies/';
$popup->refresh_manage_url();
mdcc_check( 'permalink structure changed: address follows', '/privacy/cookies/' === get_option( 'mdcc_settings' )['manage_url'] );

add_filter(
	'mdcc_manage_url',
	function ( $url ) {
		return 'https://example.test/de/cookie-einstellungen/';
	}
);
mdcc_check( 'mdcc_manage_url filter replaces the address', 'https://example.test/de/cookie-einstellungen/' === MDCC_Popup_System::get_manage_url( get_option( 'mdcc_settings' ) ) );
$GLOBALS['mdcc_test']['hooks']['mdcc_manage_url'] = array();

/* ---- whitelists, clamps and text -------------------------------------------- */

$s = $save( array( 'popup_buttons' => 'weird', 'popup_desktop_width' => '61', 'popup_radius' => '30', 'popup_bg_color' => 'red', 'popup_text_color' => '#FFF', 'popup_button_text_color' => '#12345', 'popup_inherit_font' => '1' ) );
mdcc_check( 'unknown layout becomes standard', 'standard' === $s['popup_buttons'] );
mdcc_check( 'unknown width becomes 100', 100 === $s['popup_desktop_width'] );
mdcc_check( 'radius 30 is clamped to 24', 24 === $s['popup_radius'], $s['popup_radius'] );
mdcc_check( 'colour name is dropped', '' === $s['popup_bg_color'] );
mdcc_check( 'three digit hex is kept', '#FFF' === $s['popup_text_color'] );
mdcc_check( 'five digit hex is dropped', '' === $s['popup_button_text_color'] );
mdcc_check( 'font checkbox is true', true === $s['popup_inherit_font'] );

foreach ( array( 80, 60, 50, 100 ) as $width ) {
	$s = $save( array( 'popup_desktop_width' => (string) $width ) );
	mdcc_check( "width {$width} is kept", $width === $s['popup_desktop_width'] );
}
$s = $save( array( 'popup_radius' => '0' ) );
mdcc_check( 'radius 0 is kept as 0, not as empty', 0 === $s['popup_radius'], $s['popup_radius'] );
$s = $save( array( 'popup_radius' => 'abc' ) );
mdcc_check( 'radius text becomes empty', '' === $s['popup_radius'] );

$s = $save( array( 'label_accept' => ' <b>Accept</b> all ', 'label_manage' => array( 'x' ), 'label_decline' => "No\nthanks", 'label_analytics' => '<script>alert(1)</script>' ) );
mdcc_check( 'label: tags stripped and trimmed', 'Accept all' === $s['label_accept'], $s['label_accept'] );
mdcc_check( 'label: array becomes empty', '' === $s['label_manage'] );
mdcc_check( 'label: line break becomes a space', 'No thanks' === $s['label_decline'], $s['label_decline'] );
mdcc_check( 'label: script tag leaves no markup', false === strpos( $s['label_analytics'], '<' ), $s['label_analytics'] );

echo "\nSettings: {$passed} passed, {$failed} failed\n";
exit( 0 === $failed ? 0 : 1 );
