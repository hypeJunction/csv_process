<?php

/**
 * PHPUnit bootstrap for csv_process plugin tests.
 *
 * Path layout (Docker test stack):
 *   /var/www/html/                          <- $elggRoot
 *   /var/www/html/mod/csv_process/          <- $pluginRoot
 *   /var/www/html/mod/csv_process/tests/    <- __DIR__
 *
 * tests/ -> mod/csv_process/ -> mod/ -> elgg_root/  (3 levels up)
 */

$elggRoot = dirname(__DIR__, 3);
$pluginRoot = dirname(__DIR__);

require_once $elggRoot . '/vendor/autoload.php';

// Make the framework's test base classes (UnitTestCase, IntegrationTestCase) autoloadable.
$testClassesDir = $elggRoot . '/vendor/elgg/elgg/engine/tests/classes';
spl_autoload_register(function ($class) use ($testClassesDir) {
	$file = $testClassesDir . '/' . str_replace('\\', '/', $class) . '.php';
	if (file_exists($file)) {
		require_once $file;
	}
});

// Ensure the plugin's classes are available even when the integration-test DB
// prefix has not registered the plugin entity (the test prefix is a separate
// sandbox from the production prefix that booted the plugin).
spl_autoload_register(function ($class) use ($pluginRoot) {
	$prefix = 'CsvProcess\\';
	if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
		return;
	}
	$relative = substr($class, strlen($prefix));
	$file = $pluginRoot . '/classes/CsvProcess/' . str_replace('\\', '/', $relative) . '.php';
	if (file_exists($file)) {
		require_once $file;
	}
});

\Elgg\Application::loadCore();
