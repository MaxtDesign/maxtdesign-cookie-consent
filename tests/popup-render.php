<?php
/**
 * Popup render harness
 *
 * Renders the consent popup outside WordPress, using the real plugin classes
 * and a small set of WordPress function stubs. Used by tests/popup-baseline.cjs
 * to record and compare the popup markup, the inline CSS and the popup config.
 *
 * Usage: php tests/popup-render.php '<settings json>' [privacy-url]
 * Prints one JSON object: { html, inline_css, config, inline_js_bytes }.
 *
 * The settings JSON is merged over mdcc_default_settings(), the same way a
 * saved option behaves. Dev tooling only, never shipped (/tests is in
 * .distignore and outside the build allow-list).
 *
 * @package MaxtDesign_Cookie_Consent
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['mdcc_test'] = array(
	'options'     => array(),
	'hooks'       => array(),
	'inline_css'  => array(),
	'inline_js'   => array(),
	'localized'   => array(),
	'privacy_url' => isset( $argv[2] ) ? (string) $argv[2] : '',
	'theme_dirs'  => array(),
);

/* ---- hooks ---------------------------------------------------------------- */

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	return add_filter( $hook, $callback, $priority, $args );
}

function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['mdcc_test']['hooks'][ $hook ][ $priority ][] = array( $callback, $args );
	return true;
}

function apply_filters( $hook, $value, ...$rest ) {
	if ( empty( $GLOBALS['mdcc_test']['hooks'][ $hook ] ) ) {
		return $value;
	}
	ksort( $GLOBALS['mdcc_test']['hooks'][ $hook ] );
	foreach ( $GLOBALS['mdcc_test']['hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$params = array_slice( array_merge( array( $value ), $rest ), 0, max( 1, (int) $entry[1] ) );
			$value  = call_user_func_array( $entry[0], $params );
		}
	}
	return $value;
}

function do_action( $hook, ...$params ) {
	if ( empty( $GLOBALS['mdcc_test']['hooks'][ $hook ] ) ) {
		return;
	}
	ksort( $GLOBALS['mdcc_test']['hooks'][ $hook ] );
	foreach ( $GLOBALS['mdcc_test']['hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			call_user_func_array( $entry[0], array_slice( $params, 0, (int) $entry[1] ) );
		}
	}
}

/* ---- plugin bootstrap stubs ----------------------------------------------- */

function plugin_dir_path( $file ) {
	return rtrim( str_replace( '\\', '/', dirname( $file ) ), '/' ) . '/';
}

function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/maxtdesign-cookie-consent/';
}

function plugin_basename( $file ) {
	return 'maxtdesign-cookie-consent/maxtdesign-cookie-consent.php';
}

function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}

function is_admin() {
	return false;
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['mdcc_test']['options'] ) ? $GLOBALS['mdcc_test']['options'][ $name ] : $default;
}

function is_page( $page = '' ) {
	return isset( $GLOBALS['mdcc_test']['current_page'] ) && (int) $page === (int) $GLOBALS['mdcc_test']['current_page'];
}

function _x( $text, $context, $domain = 'default' ) {
	return $text;
}

function get_privacy_policy_url() {
	return $GLOBALS['mdcc_test']['privacy_url'];
}

/* ---- pages and URLs. $GLOBALS['mdcc_test']['pages'][ id ] = array( type, status, url ) */

function home_url( $path = '' ) {
	return 'https://example.test' . ( '' !== $path ? '/' . ltrim( $path, '/' ) : '' );
}

function untrailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' );
}

function esc_url_raw( $url ) {
	return preg_match( '#^https?://#i', (string) $url ) ? (string) $url : '';
}

function get_post_type( $id ) {
	return isset( $GLOBALS['mdcc_test']['pages'][ $id ] ) ? $GLOBALS['mdcc_test']['pages'][ $id ][0] : false;
}

function get_post_status( $id ) {
	return isset( $GLOBALS['mdcc_test']['pages'][ $id ] ) ? $GLOBALS['mdcc_test']['pages'][ $id ][1] : false;
}

function get_permalink( $id ) {
	return isset( $GLOBALS['mdcc_test']['pages'][ $id ] ) ? $GLOBALS['mdcc_test']['pages'][ $id ][2] : false;
}

function update_option( $name, $value ) {
	// As in WordPress: a registered sanitize callback runs on every save.
	$GLOBALS['mdcc_test']['options'][ $name ] = apply_filters( 'sanitize_option_' . $name, $value );
	return true;
}

function sanitize_textarea_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}

/* ---- templates (MDCC_THEME_DIRS: child theme first, separated by "|") ------ */

function locate_template( $names, $load = false, $load_once = true, $args = array() ) {
	$dirs = array_filter( explode( '|', (string) getenv( 'MDCC_THEME_DIRS' ) ) );
	foreach ( (array) $names as $name ) {
		foreach ( $dirs as $dir ) {
			$candidate = rtrim( str_replace( '\\', '/', $dir ), '/' ) . '/' . $name;
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}
	}
	return '';
}

function load_template( $_template_file, $load_once = true, $args = array() ) {
	if ( $load_once ) {
		require_once $_template_file;
	} else {
		require $_template_file;
	}
}

/* ---- i18n (no translation loaded: source strings) ------------------------- */

function __( $text, $domain = 'default' ) {
	return $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( $text );
}

/* ---- escaping and sanitizing ---------------------------------------------- */

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
}

function esc_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || ! preg_match( '#^(https?://|/)#i', $url ) ) {
		return '';
	}
	return str_replace( array( '&', "'", '"', '<', '>' ), array( '&#038;', '&#039;', '%22', '%3C', '%3E' ), $url );
}

function sanitize_text_field( $text ) {
	$text = strip_tags( (string) $text );
	return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', $text ) );
}

function sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}
	return preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', (string) $color ) ? $color : null;
}

function absint( $value ) {
	return abs( (int) $value );
}

/* ---- script and style API (records what the plugin hands to WordPress) ---- */

function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	return true;
}

function wp_enqueue_style( $handle ) {}

function wp_add_inline_style( $handle, $css ) {
	$GLOBALS['mdcc_test']['inline_css'][] = $css;
	return true;
}

function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
	return true;
}

function wp_enqueue_script( $handle ) {}

function wp_localize_script( $handle, $name, $data ) {
	$GLOBALS['mdcc_test']['localized'][ $name ] = $data;
	return true;
}

function wp_add_inline_script( $handle, $js, $position = 'after' ) {
	$GLOBALS['mdcc_test']['inline_js'][] = $js;
	return true;
}

/* ---- run ------------------------------------------------------------------ */

// MDCC_RENDER_ROOT points the harness at another copy of the plugin, such as
// an extracted zip (tests/packaging-test.php).
$mdcc_root = getenv( 'MDCC_RENDER_ROOT' );
require ( $mdcc_root ? rtrim( $mdcc_root, '/\\' ) : dirname( __DIR__ ) ) . '/maxtdesign-cookie-consent.php';

$mdcc_overrides = isset( $argv[1] ) ? json_decode( (string) $argv[1], true ) : array();
if ( ! is_array( $mdcc_overrides ) ) {
	fwrite( STDERR, "Settings argument is not a JSON object.\n" );
	exit( 1 );
}

// An empty settings argument means "nothing saved": get_option() returns the
// defaults, as on a fresh install.
if ( $mdcc_overrides ) {
	$GLOBALS['mdcc_test']['options']['mdcc_settings'] = array_merge( mdcc_default_settings(), $mdcc_overrides );
}

// MDCC_TEMPLATE_FILTER makes the mdcc_popup_template filter return that path.
if ( getenv( 'MDCC_TEMPLATE_FILTER' ) ) {
	add_filter(
		'mdcc_popup_template',
		function () {
			return (string) getenv( 'MDCC_TEMPLATE_FILTER' );
		}
	);
}

// The consent runtime's config, for tests that run the real scripts.
MDCC_Consent_Manager::get_instance()->enqueue_frontend_assets();

$mdcc_popup = MDCC_Popup_System::get_instance();
$mdcc_popup->enqueue_popup_assets();

ob_start();
$mdcc_popup->render_popup();
$mdcc_html = (string) ob_get_clean();

echo json_encode(
	array(
		'html'            => str_replace( "\r\n", "\n", $mdcc_html ),
		'inline_css'      => str_replace( "\r\n", "\n", implode( "\n", $GLOBALS['mdcc_test']['inline_css'] ) ),
		'config'          => isset( $GLOBALS['mdcc_test']['localized']['mdccPopupConfig'] ) ? $GLOBALS['mdcc_test']['localized']['mdccPopupConfig'] : null,
		'runtime_config'  => isset( $GLOBALS['mdcc_test']['localized']['mdccConfig'] ) ? $GLOBALS['mdcc_test']['localized']['mdccConfig'] : null,
		'inline_js'       => implode( '', $GLOBALS['mdcc_test']['inline_js'] ),
		'inline_js_bytes' => strlen( implode( '', $GLOBALS['mdcc_test']['inline_js'] ) ),
		'template'        => method_exists( 'MDCC_Popup_System', 'locate_popup_template' ) ? str_replace( '\\', '/', MDCC_Popup_System::locate_popup_template() ) : '',
	),
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
