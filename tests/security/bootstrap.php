<?php
/**
 * Minimal bootstrap for the framework's security regression checks.
 *
 * There is no PHPUnit suite in Lightcast; these checks are plain PHP scripts run with the
 * production PHP version (7.4), e.g. `php tests/security/run.php`. They exercise the real
 * framework classes without booting an application: classes are resolved by file name
 * (`lcFoo` → `lcFoo.class.php`, `iFoo` → `iFoo.class.php`) anywhere under source/libs.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}
if (!defined('DO_DEBUG')) {
    define('DO_DEBUG', false);
}

spl_autoload_register(function ($class) {
    static $map = null;

    if ($map === null) {
        $map = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT . DS . 'source' . DS . 'libs'));
        foreach ($it as $file) {
            $name = $file->getFilename();
            if (substr($name, -10) === '.class.php') {
                $map[substr($name, 0, -10)] = $file->getPathname();
            }
        }
    }

    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

$GLOBALS['lc_test_failures'] = 0;
$GLOBALS['lc_test_count'] = 0;

/**
 * Asserts two values are identical and prints the outcome.
 *
 * @param string $label What is being checked.
 * @param mixed $expected Expected value.
 * @param mixed $actual Actual value.
 * @return void
 */
function lc_assert_same($label, $expected, $actual)
{
    $GLOBALS['lc_test_count']++;

    if ($expected === $actual) {
        echo "  ok   $label\n";
        return;
    }

    $GLOBALS['lc_test_failures']++;
    echo "  FAIL $label\n       expected: " . var_export($expected, true) . "\n       actual:   " . var_export($actual, true) . "\n";
}

/**
 * Asserts a condition holds and prints the outcome.
 *
 * @param string $label What is being checked.
 * @param bool $condition The condition.
 * @return void
 */
function lc_assert_true($label, $condition)
{
    lc_assert_same($label, true, (bool)$condition);
}
