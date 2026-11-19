<?php
/**
 * F108 (audit 2026-08-14, V15.2.1): lcRedis works with predis 1 and predis 2.
 *
 * Needs a Redis server and a predis install, so it only runs when both are given:
 *   LC_TEST_REDIS=tcp://host:6379 LC_TEST_PREDIS_AUTOLOAD=/path/to/vendor/autoload.php php tests/security/run.php
 * Run it once per predis major (point the autoloader at a predis 1 and at a predis 2 vendor dir).
 */

$redis_dsn = getenv('LC_TEST_REDIS');
$predis_autoload = getenv('LC_TEST_PREDIS_AUTOLOAD');

if (!$redis_dsn || !$predis_autoload) {
    echo "  skip (set LC_TEST_REDIS and LC_TEST_PREDIS_AUTOLOAD)\n";
    return;
}

require_once $predis_autoload;

echo '  predis ' . Predis\Client::VERSION . "\n";

$store = new lcRedis();
$store->setOptions(['servers' => [$redis_dsn]]);

$prefix = 'lc-test-' . bin2hex(random_bytes(4)) . ':';

lc_assert_true('set with lifetime', (bool)$store->set($prefix . 'a', ['x' => 1], ['lifetime' => 30]));
lc_assert_same('get returns the unserialized value', ['x' => 1], $store->get($prefix . 'a'));
lc_assert_same('ttl applied', true, $store->getBackend()->ttl($prefix . 'a') > 0);
lc_assert_true('has', $store->has($prefix . 'a'));

// used to fail: SETEX with an empty TTL is rejected by Redis
lc_assert_true('set without lifetime', (bool)$store->set($prefix . 'b', 'forever'));
lc_assert_same('no expiry', -1, $store->getBackend()->ttl($prefix . 'b'));
lc_assert_same('get b', 'forever', $store->get($prefix . 'b'));

lc_assert_same('missing key', null, $store->get($prefix . 'missing'));
lc_assert_same('remove', 1, $store->remove($prefix . 'a'));
lc_assert_same('removed', null, $store->get($prefix . 'a'));

// SET NX EX (used by applications for atomic one-time markers, e.g. OAuth nonces)
$client = $store->getCachingSystem();
lc_assert_true('SET NX EX first', (bool)$client->set($prefix . 'n', '1', 'EX', 30, 'NX'));
lc_assert_same('SET NX EX second is refused', null, $client->set($prefix . 'n', '1', 'EX', 30, 'NX'));

$client->del([$prefix . 'b', $prefix . 'n']);

$threw = false;
try {
    (new lcRedis())->getBackend();
} catch (lcConfigException $e) {
    $threw = true;
}
lc_assert_true('no server configured is a clear config error', $threw);
