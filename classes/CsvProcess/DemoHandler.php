<?php

namespace CsvProcess;

use Elgg\Event;

/**
 * Demo CSV row handler. Registered on the `csv_process,callbacks` event
 * so administrators can pick it from the dropdown in the admin UI.
 *
 * The per-row callback signature (what `$csv_callback($params)` receives
 * in `CsvProcessor::processCsv`) is intentionally a positional `array`
 * payload, NOT an `\Elgg\Event`. csv_process invokes the registered
 * callable directly, so consumer plugins (e.g. `bodyology_csv`) keep
 * their per-row functions taking `array $params`.
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
	 * @param Event $event 'csv_process','callbacks' event
	 *
	 * @return array
	 */
	public static function register(Event $event) {
		$return = (array) $event->getValue();

		$return[self::class . '::handle'] = \elgg_echo('csv_process:handler:label');

		return $return;
	}

	/**
	 * Per-row demo handler. Returns a log line for each row, plus a summary
	 * on the synthetic final invocation (`$params['last'] === true`).
	 *
	 * @param array $params per-row callback parameters
	 *                      (keys: 'data', 'line', 'last', 'time')
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
