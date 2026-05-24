<?php

return [
	'plugin' => [
		'name' => 'CSV Process',
		'activate_on_install' => false,
	],
	'bootstrap' => 'CsvProcess\\Bootstrap',
	'actions' => [
		'csv_process' => [
			'access' => 'admin',
		],
		'csv_process/log_download' => [
			'access' => 'admin',
		],
	],
	'hooks' => [
		'csv_process' => [
			'callbacks' => [
				'CsvProcess\\DemoHandler::register' => [],
			],
		],
	],
	'menus' => [
		'page' => [
			'csv_process' => [
				'text' => 'CSV Processing',
				'href' => 'admin/administer_utilities/csv_process',
				'parent_name' => 'administer_utilities',
				'section' => 'configure',
				'context' => 'admin',
			],
		],
	],
];
