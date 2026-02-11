<?php
/**
 * JavaScript Size Validation
 * Run: php tools/js-size-check.php
 */

$original = __DIR__ . '/../assets/js/consent-runtime.js.backup';
$optimized = __DIR__ . '/../assets/js/consent-runtime.js';

if (!file_exists($original)) {
    die("ERROR: Backup file not found. Run Step 1 first.\n");
}

$original_size = filesize($original);
$optimized_size = filesize($optimized);
$reduction = $original_size - $optimized_size;
$percent = round(($reduction / $original_size) * 100, 1);

echo "=== JavaScript Optimization Results ===\n";
echo "Original:   " . number_format($original_size) . " bytes\n";
echo "Optimized:  " . number_format($optimized_size) . " bytes\n";
echo "Reduction:  " . number_format($reduction) . " bytes ({$percent}%)\n";
echo "Target:     4,608 bytes (4.5KB)\n";
echo "\n";

if ($optimized_size <= 4608) {
    echo "✅ SUCCESS - Target met!\n";
    echo "Next: Phase 3 minification will reduce to ~2.5KB\n";
    exit(0);
} else {
    $over = $optimized_size - 4608;
    echo "⚠️  Still over target by " . number_format($over) . " bytes\n";
    echo "Need to reduce by another " . round(($over / $optimized_size) * 100, 1) . "%\n";
    exit(1);
}
