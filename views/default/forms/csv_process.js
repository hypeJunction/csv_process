define(function (require) {

	var elgg = require('elgg');
	var $ = require('jquery');
	var spinner = require('elgg/spinner');
	var i18n = require('elgg/i18n');
	require('jquery.form');

	$('form.elgg-form-csv-process').submit(function (e) {
		e.preventDefault();
		if (confirm(i18n.echo('csv_process:form:confirm'))) {
			var $form = $(this);
			$form.ajaxSubmit({
				dataType: 'json',
				beforeSend: spinner.start,
				complete: spinner.stop,
				success: function(response) {
					if (response.output && response.output.progress) {
						$('#csv-process-results-placeholder').html($(response.output.progress));
					}
				}
			});
		}

		return false;
	});

});
