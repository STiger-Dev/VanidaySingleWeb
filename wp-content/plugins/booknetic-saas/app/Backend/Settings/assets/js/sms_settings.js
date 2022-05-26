(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
		{
			var sms_account_sid			        = $("#input_sms_account_sid").val(),
				sms_auth_token			        = $("#input_sms_auth_token").val(),
				sender_phone_number		        = $("#input_sender_phone_number").val(),
				sender_phone_number_whatsapp	= $("#input_sender_phone_number_whatsapp").val();

			var data = new FormData();

			data.append('sms_account_sid', sms_account_sid);
			data.append('sms_auth_token', sms_auth_token);
			data.append('sender_phone_number', sender_phone_number);
			data.append('sender_phone_number_whatsapp', sender_phone_number_whatsapp);

			booknetic.ajax('save_settings', data, function()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		});

	});

})(jQuery);