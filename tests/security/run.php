<?php
/**
 * Runs every security regression check in this directory; exit code 1 if any fails.
 */
require __DIR__ . '/bootstrap.php';

foreach (glob(__DIR__ . '/*Test.php') as $test) {
    echo basename($test) . "\n";
    require $test;
}

echo "\n" . $GLOBALS['lc_test_count'] . ' checks, ' . $GLOBALS['lc_test_failures'] . " failed\n";
exit($GLOBALS['lc_test_failures'] ? 1 : 0);
