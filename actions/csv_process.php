<?php

use CsvProcess\CsvProcessor;

set_time_limit(0);

$callback = (string) get_input('callback', '');
$options = (array) elgg_trigger_event_results('csv_process', 'callbacks', [], []);
$location = (string) get_input('location', '');
$delimiter = (string) get_input('delimiter', ',');
$enclosure = (string) get_input('enclosure', '"');
$escape = (string) get_input('escape', '\\');

if (!$callback || !in_array($callback, array_keys($options))) {
	return elgg_error_response(elgg_echo('csv_process:error:invalid:callback'));
}

if (!is_callable($callback)) {
	return elgg_error_response(elgg_echo('csv_process:error:uncallable:callback'));
}

if (empty($delimiter) || empty($escape) || empty($enclosure)) {
	return elgg_error_response(elgg_echo('csv_process:error:empty:args'));
}

if ((empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error']) && !$location) {
	return elgg_error_response(elgg_echo('csv_process:error:upload'));
}

elgg_register_event_handler('shutdown', 'system', [CsvProcessor::class, 'processCsv']);

$time = time();
$csv_location = $location ? $location : $_FILES['csv']['tmp_name'];

elgg_set_config('csv_process_time', $time);
elgg_set_config('csv_process_location', $csv_location);
elgg_set_config('csv_process_callback', $callback);
elgg_set_config('csv_process_delimiter', $delimiter);
elgg_set_config('csv_process_enclosure', $enclosure);
elgg_set_config('csv_process_escape', $escape);

if (!file_exists(elgg_get_config('dataroot') . 'csv_process_log')) {
	mkdir(elgg_get_config('dataroot') . 'csv_process_log');
}

if (elgg_is_xhr()) {
	return elgg_ok_response([
		'progress' => elgg_view('csv_process/ajax/progress', [
			'time' => $time,
			'full_view' => true,
		]),
	]);
}

return elgg_redirect_response('admin/administer_utilities/csv_process?time=' . $time);
