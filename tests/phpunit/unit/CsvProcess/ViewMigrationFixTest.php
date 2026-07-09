<?php

namespace CsvProcess;

use Elgg\UnitTestCase;

/**
 * Source-level regression guards for view-layer migration fixes that have no
 * runtime hook to assert against without a full HTTP render.
 *
 *  - 7c4b5f9: progress view must call the 7.x-correct elgg_admin_gatekeeper()
 *             (the legacy admin_gatekeeper() was removed and fatals).
 *  - b1ac96f: 6.x AMD->ESM conversion — forms view must import the ESM module
 *             via elgg_import_esm() and must NOT emit a legacy require([...]).
 */
class ViewMigrationFixTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	private function pluginRoot(): string {
		// Walk up to the directory holding the plugin manifest rather than counting
		// path segments: this file sits at tests/phpunit/unit/CsvProcess/, so a fixed
		// dirname() depth silently resolved to tests/ and every assertFileExists()
		// below failed on a path that never existed.
		$dir = __DIR__;
		for ($i = 0; $i < 6; $i++) {
			if (is_file($dir . '/elgg-plugin.php') || is_file($dir . '/manifest.xml')) {
				return $dir;
			}

			$dir = dirname($dir);
		}

		throw new \RuntimeException('Unable to locate plugin root from ' . __DIR__);
	}

	private function readView(string $relative): string {
		$path = $this->pluginRoot() . '/' . $relative;
		$this->assertFileExists($path);

		return (string) file_get_contents($path);
	}

	public function testProgressViewUsesElggAdminGatekeeper(): void {
		$src = $this->readView('views/default/csv_process/ajax/progress.php');

		$this->assertMatchesRegularExpression(
			'/(?<![\w\\\\])elgg_admin_gatekeeper\s*\(/',
			$src,
			'progress view must call elgg_admin_gatekeeper() to deny non-admins on 7.x'
		);
		// The removed 6.x name admin_gatekeeper() (not prefixed by elgg_) fatals.
		$this->assertDoesNotMatchRegularExpression(
			'/(?<![\w\\\\])admin_gatekeeper\s*\(/',
			$src,
			'progress view still calls the removed admin_gatekeeper() — replace with elgg_admin_gatekeeper()'
		);
	}

	public function testFormsViewImportsEsmAndDropsLegacyRequire(): void {
		$src = $this->readView('views/default/forms/csv_process.php');

		$this->assertStringContainsString(
			"elgg_import_esm('forms/csv_process')",
			$src,
			'forms view must load its ESM module via elgg_import_esm()'
		);
		$this->assertDoesNotMatchRegularExpression(
			'/require\s*\(\s*\[/',
			$src,
			'forms view still emits a legacy AMD require([...]) block'
		);
		$this->assertDoesNotMatchRegularExpression(
			'/(?<![\w>$:\\\\])elgg_require_js\s*\(/',
			$src,
			'forms view still calls the removed elgg_require_js()'
		);
	}

	public function testEsmModulesReplacedLegacyAmdFiles(): void {
		$root = $this->pluginRoot();

		$this->assertFileExists($root . '/views/default/forms/csv_process.mjs');
		$this->assertFileExists($root . '/views/default/csv_process/ajax/progress.mjs');

		// The AMD-era .js twins must be gone after the ESM conversion.
		$this->assertFileDoesNotExist($root . '/views/default/forms/csv_process.js');
		$this->assertFileDoesNotExist($root . '/views/default/csv_process/ajax/progress.js');
	}
}
