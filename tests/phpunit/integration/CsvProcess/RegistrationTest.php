<?php

namespace CsvProcess;

use Elgg\IntegrationTestCase;

/**
 * Boots the plugin on a real Elgg 7 and asserts elgg-plugin.php actually wired
 * up its actions, its declarative callbacks event, and its admin.css extension.
 */
class RegistrationTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'csv_process';
	}

	public function testActionsAreRegistered(): void {
		$actions = _elgg_services()->actions;

		$this->assertTrue(
			$actions->exists('csv_process'),
			"'csv_process' action was not registered from elgg-plugin.php"
		);
		$this->assertTrue(
			$actions->exists('csv_process/log_download'),
			"'csv_process/log_download' action was not registered from elgg-plugin.php"
		);
	}

	public function testCallbacksEventRegistersDemoHandler(): void {
		// Declarative 'events' => ['csv_process' => ['callbacks' => [...]]] must
		// wire DemoHandler::register (an \Elgg\Event handler after the 5.x fix)
		// so triggering the event yields the demo callable in the map.
		$options = elgg_trigger_event_results('csv_process', 'callbacks', [], []);

		$this->assertIsArray($options);
		$key = DemoHandler::class . '::handle';
		$this->assertArrayHasKey(
			$key,
			$options,
			"csv_process,callbacks event did not register DemoHandler::handle — declarative 'events' wiring broken"
		);
		$this->assertSame(elgg_echo('csv_process:handler:label'), $options[$key]);
	}

	public function testAdminCssExtendedWithPluginCss(): void {
		// Bootstrap::init() extends 'admin.css' with 'csv_process.css'
		// (migration fix f4e4e94 moved this out of start.php).
		$css = elgg_view('admin.css');

		$this->assertStringContainsString(
			'#csv-process-results',
			$css,
			'admin.css was not extended with csv_process.css — Bootstrap::init did not run'
		);
	}
}
