<?php

namespace CsvProcess;

/**
 * Streams a CSV file row-by-row and dispatches each row to the configured
 * per-row callback. Triggered from a system shutdown handler so processing
 * happens after the HTTP response is flushed.
 */
class CsvProcessor {

	/**
	 * Process the CSV file referenced by the persisted run configuration.
	 *
	 * @return void
	 */
	public static function processCsv() {
		ini_set('auto_detect_line_endings', true);

		$time = \elgg_get_config('csv_process_time');
		$location = \elgg_get_config('csv_process_location');
		$csv_callback = \elgg_get_config('csv_process_callback');
		$delimiter = \elgg_get_config('csv_process_delimiter');
		$enclosure = \elgg_get_config('csv_process_enclosure');
		$escape = \elgg_get_config('csv_process_escape');

		$lines = 0;
		$handle = fopen($location, 'r');
		if ($handle !== false) {
			while (true) {
				$data = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
				if ($data === false) {
					break;
				}

				$lines++;

				$params = ['data' => $data, 'line' => $lines, 'last' => false, 'time' => $time];
				$log = $csv_callback($params);

				if ($log) {
					self::writeLog($log, $params);
				}
			}

			fclose($handle);
		}

		// call the callback one last time with the final line count so they can log summary info
		$log = $csv_callback(['data' => [], 'time' => $time, 'line' => $lines, 'last' => true]);
		if ($log) {
			self::writeLog($log, ['time' => $time]);
		}

		self::writeLog(\elgg_echo('csv_process:complete', [$lines]), ['time' => $time]);
	}

	/**
	 * Append a log line to the per-run CSV processing log file.
	 *
	 * @param mixed $message log line to write
	 * @param array $params  per-row callback parameters (uses 'time' key for filename)
	 *
	 * @return void
	 */
	public static function writeLog($message, array $params) {
		file_put_contents(
			\elgg_get_config('dataroot') . "csv_process_log/{$params['time']}log.txt",
			$message . "\n",
			FILE_APPEND
		);
	}
}
