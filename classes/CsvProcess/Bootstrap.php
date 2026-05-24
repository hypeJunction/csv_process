<?php

namespace CsvProcess;

use Elgg\DefaultPluginBootstrap;

/**
 * Plugin bootstrap.
 *
 * Replaces the 3.x start.php closure. Only runtime wiring that cannot be
 * expressed declaratively in elgg-plugin.php belongs here.
 */
class Bootstrap extends DefaultPluginBootstrap {

	/**
	 * Runtime registration step.
	 *
	 * @return void
	 */
	public function init() {
		\elgg_extend_view('admin.css', 'csv_process.css');
	}
}
