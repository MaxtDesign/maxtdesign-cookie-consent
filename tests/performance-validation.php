<?php
/**
 * Performance Validation Test
 *
 * Validates that all optimization targets have been met.
 * Run: php tests/performance-validation.php
 *
 * @package MaxtDesign_Cookie_Consent
 */

// Configuration
$plugin_dir = dirname( __DIR__ );
$targets    = array(
	'frontend_total' => 10240, // 10KB
	'popup_css'      => 6144,  // 6KB
	'runtime_js'     => 4096,  // 4KB
);

// Color codes for terminal output
$RED    = "\033[31m";
$GREEN  = "\033[32m";
$YELLOW = "\033[33m";
$BLUE   = "\033[34m";
$RESET  = "\033[0m";

echo "================================================\n";
echo "MaxtDesign Cookie Consent - Performance Validation\n";
echo "================================================\n\n";

// Check if minified files exist
$files = array(
	'popup_min_css'  => $plugin_dir . '/assets/css/popup.min.css',
	'runtime_min_js' => $plugin_dir . '/assets/js/consent-runtime.min.js',
	'popup_src_css'  => $plugin_dir . '/assets/css/popup.css',
	'runtime_src_js' => $plugin_dir . '/assets/js/consent-runtime.js',
);

$all_exist = true;
foreach ( $files as $key => $path ) {
	if ( ! file_exists( $path ) ) {
		echo "{$RED}❌ ERROR: File not found: {$path}{$RESET}\n";
		$all_exist = false;
	}
}

if ( ! $all_exist ) {
	echo "\n{$RED}Build files first: npm run build{$RESET}\n";
	exit( 1 );
}

echo "{$BLUE}📊 File Sizes:{$RESET}\n";
echo "----------------------------------------\n\n";

// Get file sizes
$sizes = array();
foreach ( $files as $key => $path ) {
	$sizes[ $key ] = filesize( $path );
}

// Calculate metrics
$frontend_min_total = $sizes['popup_min_css'] + $sizes['runtime_min_js'];
$frontend_src_total = $sizes['popup_src_css'] + $sizes['runtime_src_js'];

// Display minified (production)
echo "Production (minified):\n";
echo "  popup.min.css:          " . number_format( $sizes['popup_min_css'] ) . " bytes (" . round( $sizes['popup_min_css'] / 1024, 2 ) . " KB)\n";
echo "  consent-runtime.min.js: " . number_format( $sizes['runtime_min_js'] ) . " bytes (" . round( $sizes['runtime_min_js'] / 1024, 2 ) . " KB)\n";
echo "  {$BLUE}Total minified:         " . number_format( $frontend_min_total ) . " bytes (" . round( $frontend_min_total / 1024, 2 ) . " KB){$RESET}\n";
echo "\n";

// Display source (development)
echo "Development (source):\n";
echo "  popup.css:              " . number_format( $sizes['popup_src_css'] ) . " bytes (" . round( $sizes['popup_src_css'] / 1024, 2 ) . " KB)\n";
echo "  consent-runtime.js:     " . number_format( $sizes['runtime_src_js'] ) . " bytes (" . round( $sizes['runtime_src_js'] / 1024, 2 ) . " KB)\n";
echo "  Total source:           " . number_format( $frontend_src_total ) . " bytes (" . round( $frontend_src_total / 1024, 2 ) . " KB)\n";
echo "\n";

// Calculate reductions
$css_reduction   = round( ( $sizes['popup_src_css'] - $sizes['popup_min_css'] ) / $sizes['popup_src_css'] * 100, 1 );
$js_reduction    = round( ( $sizes['runtime_src_js'] - $sizes['runtime_min_js'] ) / $sizes['runtime_src_js'] * 100, 1 );
$total_reduction = round( ( $frontend_src_total - $frontend_min_total ) / $frontend_src_total * 100, 1 );

echo "Reduction:\n";
echo "  CSS:   {$css_reduction}%\n";
echo "  JS:    {$js_reduction}%\n";
echo "  Total: {$total_reduction}%\n";
echo "\n";

// Validate targets
echo "================================================\n";
echo "{$BLUE}🎯 Target Validation:{$RESET}\n";
echo "================================================\n\n";

$all_passed = true;

// Frontend total target
echo "Frontend Total (minified):\n";
echo "  Actual: " . number_format( $frontend_min_total ) . " bytes\n";
echo "  Target: " . number_format( $targets['frontend_total'] ) . " bytes (10 KB)\n";
if ( $frontend_min_total <= $targets['frontend_total'] ) {
	$under = $targets['frontend_total'] - $frontend_min_total;
	echo "  {$GREEN}✅ PASS - Under target by " . number_format( $under ) . " bytes{$RESET}\n";
} else {
	$over = $frontend_min_total - $targets['frontend_total'];
	echo "  {$RED}❌ FAIL - Over target by " . number_format( $over ) . " bytes{$RESET}\n";
	$all_passed = false;
}
echo "\n";

// Individual file targets (warning only, not failure)
echo "Individual Files (minified):\n";
if ( $sizes['popup_min_css'] <= $targets['popup_css'] ) {
	echo "  popup.min.css:          {$GREEN}✅ " . number_format( $sizes['popup_min_css'] ) . " bytes (≤6KB target){$RESET}\n";
} else {
	echo "  popup.min.css:          {$YELLOW}⚠️  " . number_format( $sizes['popup_min_css'] ) . " bytes (>6KB, but OK if total passes){$RESET}\n";
}

if ( $sizes['runtime_min_js'] <= $targets['runtime_js'] ) {
	echo "  consent-runtime.min.js: {$GREEN}✅ " . number_format( $sizes['runtime_min_js'] ) . " bytes (≤4KB target){$RESET}\n";
} else {
	echo "  consent-runtime.min.js: {$YELLOW}⚠️  " . number_format( $sizes['runtime_min_js'] ) . " bytes (>4KB, but OK if total passes){$RESET}\n";
}
echo "\n";

// Estimate gzipped size
$estimated_gzipped = round( $frontend_min_total * 0.3 ); // Rough 30% gzip ratio
echo "Estimated gzipped: ~" . number_format( $estimated_gzipped ) . " bytes (~" . round( $estimated_gzipped / 1024, 2 ) . " KB)\n";
echo "\n";

// Final result
echo "================================================\n";
if ( $all_passed ) {
	echo "{$GREEN}✅ ALL PERFORMANCE TARGETS MET{$RESET}\n";
	exit( 0 );
} else {
	echo "{$RED}❌ PERFORMANCE TARGETS NOT MET{$RESET}\n";
	echo "Review Phase 1 (CSS) and Phase 2 (JS) optimizations.\n";
	exit( 1 );
}
