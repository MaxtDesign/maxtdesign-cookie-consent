<?php
/**
 * Functional Tests
 *
 * Basic automated checks for plugin functionality.
 * Note: This is NOT a replacement for manual testing.
 * Run: php tests/functional-tests.php
 *
 * @package MaxtDesign_Cookie_Consent
 */

// Load WordPress (adjust path as needed for your test environment)
// require_once '/path/to/wordpress/wp-load.php';

// Color codes
$RED    = "\033[31m";
$GREEN  = "\033[32m";
$YELLOW = "\033[33m";
$BLUE   = "\033[34m";
$RESET  = "\033[0m";

echo "================================================\n";
echo "MaxtDesign Cookie Consent - Functional Tests\n";
echo "================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

/**
 * Test helper function
 *
 * @param string $description Test description.
 * @param bool   $condition   Test condition.
 * @return bool
 */
function test( $description, $condition ) {
	global $tests_passed, $tests_failed, $GREEN, $RED, $RESET;

	if ( $condition ) {
		echo "{$GREEN}✅ PASS:{$RESET} {$description}\n";
		++$tests_passed;
		return true;
	} else {
		echo "{$RED}❌ FAIL:{$RESET} {$description}\n";
		++$tests_failed;
		return false;
	}
}

// Test 1: Plugin files exist
echo "{$BLUE}File Structure Tests:{$RESET}\n";
$plugin_dir = dirname( __DIR__ );
test( 'Main plugin file exists', file_exists( $plugin_dir . '/maxtdesign-cookie-consent.php' ) );
test( 'Popup system class exists', file_exists( $plugin_dir . '/includes/class-popup-system.php' ) );
test( 'Consent manager class exists', file_exists( $plugin_dir . '/includes/class-consent-manager.php' ) );
test( 'Minified CSS exists', file_exists( $plugin_dir . '/assets/css/popup.min.css' ) );
test( 'Minified JS exists', file_exists( $plugin_dir . '/assets/js/consent-runtime.min.js' ) );
test( 'Source CSS exists', file_exists( $plugin_dir . '/assets/css/popup.css' ) );
test( 'Source JS exists', file_exists( $plugin_dir . '/assets/js/consent-runtime.js' ) );
echo "\n";

// Test 2: Minified files are smaller than source
echo "{$BLUE}Minification Tests:{$RESET}\n";
$css_src = filesize( $plugin_dir . '/assets/css/popup.css' );
$css_min = filesize( $plugin_dir . '/assets/css/popup.min.css' );
test( 'popup.min.css is smaller than popup.css', $css_min < $css_src );

$js_src  = filesize( $plugin_dir . '/assets/js/consent-runtime.js' );
$js_min  = filesize( $plugin_dir . '/assets/js/consent-runtime.min.js' );
test( 'consent-runtime.min.js is smaller than consent-runtime.js', $js_min < $js_src );
echo "\n";

// Test 3: No syntax errors in PHP files
echo "{$BLUE}PHP Syntax Tests:{$RESET}\n";
$php_files = glob( $plugin_dir . '/includes/*.php' );
foreach ( $php_files as $file ) {
	$output    = array();
	$return_var = 0;
	exec( 'php -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $return_var );
	$filename  = basename( $file );
	test( "{$filename} has no syntax errors", 0 === $return_var );
}
echo "\n";

// Test 4: No syntax errors in JavaScript files
echo "{$BLUE}JavaScript Syntax Tests:{$RESET}\n";
$js_files = array(
	'consent-runtime.js'     => $plugin_dir . '/assets/js/consent-runtime.js',
	'consent-runtime.min.js' => $plugin_dir . '/assets/js/consent-runtime.min.js',
);
foreach ( $js_files as $name => $path ) {
	$content = file_get_contents( $path );
	test( "{$name} is not empty", strlen( $content ) > 0 );
	test( "{$name} contains expected code", false !== strpos( $content, 'mdcc' ) );
}
echo "\n";

// Test 5: CSS contains expected classes
echo "{$BLUE}CSS Content Tests:{$RESET}\n";
$css_content     = file_get_contents( $plugin_dir . '/assets/css/popup.css' );
$css_min_content = file_get_contents( $plugin_dir . '/assets/css/popup.min.css' );

$expected_classes = array( '.mdcc-popup', '.mdcc-popup--visible', '.mdcc-popup__button', '.mdcc-popup--style-minimal', '.mdcc-popup--style-modern', '.mdcc-popup--style-bold' );
foreach ( $expected_classes as $class ) {
	test( "CSS contains {$class}", false !== strpos( $css_content, $class ) );
}
test( 'Minified CSS contains core classes', false !== strpos( $css_min_content, '.mdcc-popup' ) );
echo "\n";

// Test 6: Build files exist
echo "{$BLUE}Build System Tests:{$RESET}\n";
test( 'package.json exists', file_exists( $plugin_dir . '/package.json' ) );
test( 'postcss.config.js exists', file_exists( $plugin_dir . '/postcss.config.js' ) );
test( 'Build script exists (Unix)', file_exists( $plugin_dir . '/tools/build.sh' ) );
test( 'Build script exists (Windows)', file_exists( $plugin_dir . '/tools/build.ps1' ) );
test( 'Size validation script exists', file_exists( $plugin_dir . '/tools/validate-size.js' ) );
echo "\n";

// Final summary
echo "================================================\n";
echo "Test Results:\n";
echo "  {$GREEN}Passed: {$tests_passed}{$RESET}\n";
if ( $tests_failed > 0 ) {
	echo "  {$RED}Failed: {$tests_failed}{$RESET}\n";
} else {
	echo "  Failed: 0\n";
}
echo "================================================\n\n";

if ( 0 === $tests_failed ) {
	echo "{$GREEN}✅ ALL AUTOMATED TESTS PASSED{$RESET}\n";
	echo "{$YELLOW}⚠️  Run manual tests to validate visual/functional aspects{$RESET}\n";
	exit( 0 );
} else {
	echo "{$RED}❌ SOME TESTS FAILED{$RESET}\n";
	exit( 1 );
}
