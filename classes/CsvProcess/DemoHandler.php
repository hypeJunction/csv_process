<?php

namespace CsvProcess;

use Elgg\Hook;

/**
 * Demo CSV row handler. Registered on the `csv_process,callbacks` plugin
 * hook so administrators can pick it from the dropdown in the admin UI.
 *
 * The hook signature is preserved at 4 args because consumer plugins such as
 * `bodyology_csv` register callable maps using the legacy 4-arg signature;
 * unifying to `\Elgg\Hook` will happen at the 4.x -> 5.x boundary
 * (`elgg-migrate-xk2ch`).
 */
class DemoHandler {

	/**
	 * Register this plugin's demo handler in the `csv_process,callbacks` map.
	 *
	 * The map key is a string callable (`Class::method`) so that
	 * `is_callable($callback)` succeeds in actions/csv_process.php and
	 * `$csv_callback($params)` in CsvProcessor::processCsv() resolves to
	 * the static method.
	 *
	 * @param Hook $hook hook
	 *
	 * @return array
	 */
	public static function register(Hook $hook) {
		$return = (array) $hook->getValue();

		$return[self::class . '::handle'] = \elgg_echo('csv_process:handler:label');

		return $return;
	}

	/**
	 * Per-row demo handler. Returns a log line for each row, plus a summary
	 * on the synthetic final invocation (`$params['last'] === true`).
	 *
	 * @param array $params per-row callback parameters
	 *
	 * @return string|false
	 */
	public static function handle(array $params) {
		if ($params['last']) {
			return "Log some summary information... after {$params['line']} lines";
		}

		// sleeping as this is super-fast, lets let the log show for the demo
		sleep(1);

		// log every line here for demo purposes
		// for something like a line count it's best for performance not to log every line
		// you can log intervals of lines with a modulus check
		// eg. if (!($line % 100)) { return $line . ' lines processed'; } else { return false; }
		// will log every 100th line
		return "First Cell: {$params['data'][0]}, Line: {$params['line']}";
	}
}
