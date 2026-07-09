<?php

namespace CsvProcess;

use Elgg\IntegrationTestCase;

/**
 * Exercises the streaming processor end-to-end: it must read the file with the
 * configured delimiter/enclosure/escape, invoke the callback per row, invoke it
 * a final time with last=true, and append a completion line with the row count.
 */
class CsvProcessorTest extends IntegrationTestCase {

	protected string $time;
	protected string $csvPath;
	protected string $logDir;
	protected string $logPath;

	public function up() {
		$this->time = (string) (time() . random_int(1000, 9999));

		$dataroot = elgg_get_config('dataroot');
		$this->logDir = $dataroot . 'csv_process_log';
		if (!is_dir($this->logDir)) {
			mkdir($this->logDir, 0755, true);
		}
		$this->logPath = $this->logDir . "/{$this->time}log.txt";

		$this->csvPath = tempnam(sys_get_temp_dir(), 'csvproc') . '.csv';
		file_put_contents($this->csvPath, "Alpha,Beta\n");

		elgg_set_config('csv_process_time', $this->time);
		elgg_set_config('csv_process_location', $this->csvPath);
		elgg_set_config('csv_process_callback', DemoHandler::class . '::handle');
		elgg_set_config('csv_process_delimiter', ',');
		elgg_set_config('csv_process_enclosure', '"');
		elgg_set_config('csv_process_escape', '\\');
	}

	public function down() {
		foreach ([$this->csvPath, $this->logPath] as $file) {
			if ($file && is_file($file)) {
				unlink($file);
			}
		}
	}

	public function testProcessCsvStreamsRowsThroughCallbackThenCompletes(): void {
		CsvProcessor::processCsv();

		$this->assertFileExists($this->logPath, 'processCsv() did not write a per-run log file');
		$log = (string) file_get_contents($this->logPath);

		// Per-row callback output (data[0] = first cell, line = 1-based index).
		$this->assertStringContainsString(
			'First Cell: Alpha, Line: 1',
			$log,
			'processor did not invoke the callback for the CSV row'
		);
		// Final callback invocation with last=true and line = total rows.
		$this->assertStringContainsString(
			'Log some summary information... after 1 lines',
			$log,
			'processor did not invoke the callback a final time with last=true'
		);
		// Completion line carries the total processed line count.
		$this->assertStringContainsString(
			elgg_echo('csv_process:complete', [1]),
			$log,
			'processor did not append the completion summary with the row count'
		);
	}

	public function testWriteLogAppendsNewlineTerminatedLines(): void {
		CsvProcessor::writeLog('first entry', ['time' => $this->time]);
		CsvProcessor::writeLog('second entry', ['time' => $this->time]);

		$this->assertSame(
			"first entry\nsecond entry\n",
			(string) file_get_contents($this->logPath),
			'writeLog must append each message followed by a newline (FILE_APPEND)'
		);
	}
}
