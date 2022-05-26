(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
		{
			var currency							= $("#input_currency").val(),
				currency_symbol						= $("#input_currency_symbol").val(),
				currency_format						= $("#input_currency_format").val(),
				tenant_default_currency				= $("#input_tenant_default_currency").val(),
				tenant_default_currency_symbol		= $("#input_tenant_default_currency_symbol").val(),
				tenant_default_currency_format		= $("#input_tenant_default_currency_format").val(),
				price_number_format					= $("#input_price_number_format").val(),
				price_number_of_decimals			= $("#input_price_number_of_decimals").val();

			booknetic.ajax('save_payments_settings', {
				currency: currency,
				currency_symbol: currency_symbol,
				currency_format: currency_format,
				tenant_default_currency,
				tenant_default_currency_symbol,
				tenant_default_currency_format,
				price_number_format: price_number_format,
				price_number_of_decimals: price_number_of_decimals
			}, function ()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		}).on('change', '#input_currency', function ()
		{
			var symbol = $(this).children(':selected').data('symbol');
			$('#input_currency_symbol').val( symbol );
		}).on('change', '#input_tenant_default_currency', function ()
		{
			var symbol = $(this).children(':selected').data('symbol');
			$('#input_tenant_default_currency_symbol').val( symbol );
		});


		$("#input_currency, #input_currency_format,#input_tenant_default_currency, #input_tenant_default_currency_format, #input_price_number_format, #input_price_number_of_decimals").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select'),
			allowClear: true
		});

	});

})(jQuery);