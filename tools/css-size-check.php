<?php
/**
 * CSS Size Validation
 * Run: php tools/css-size-check.php
 */

$original = __DIR__ . '/../assets/css/popup.css.backup';
$refactored = __DIR__ . '/../assets/css/popup.css';

if (!file_exists($original)) {
    die("ERROR: Backup file not found. Run Step 1 first.\n");
}

$original_size = filesize($original);
$refactored_size = filesize($refactored);
$reduction = $original_size - $refactored_size;
$percent = round(($reduction / $original_size) * 100, 1);

echo "=== CSS Refactoring Results ===\n";
echo "Original:    " . number_format($original_size) . " bytes\n";
echo "Refactored:  " . number_format($refactored_size) . " bytes\n";
echo "Reduction:   " . number_format($reduction) . " bytes ({$percent}%)\n";
echo "Target:      8,192 bytes (8KB)\n";
echo "\n";

if ($refactored_size <= 8192) {
    echo "✅ SUCCESS - Target met!\n";
    echo "Next: Phase 3 minification will reduce to ~5KB\n";
    exit(0);
} else {
    $over = $refactored_size - 8192;
    echo "⚠️  Still over target by " . number_format($over) . " bytes\n";
    echo "Need to reduce by another " . round(($over / $refactored_size) * 100, 1) . "%\n";
    exit(1);
}
