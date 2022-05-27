(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$('#booknetic_settings_area').on('change', '#input_mail_gateway', function()
		{
			if( $(this).val() == 'smtp' )
			{
				$(".smtp_details").slideDown(300);
			}
			else
			{
				$(".smtp_details").slideUp(300);
			}
		}).on('click', '.settings-save-btn', function ()
		{
			var mail_gateway	= $("#input_mail_gateway").val(),
				smtp_hostname	= $("#input_smtp_hostname").val(),
				smtp_port		= $("#input_smtp_port").val(),
				smtp_secure		= $("#input_smtp_secure").val(),
				smtp_username	= $("#input_smtp_username").val(),
				smtp_password	= $("#input_smtp_password").val(),
				sender_email	= $("#input_sender_email").val(),
				sender_name		= $("#input_sender_name").val();

			var data = new FormData();

			data.append('mail_gateway', mail_gateway);
			data.append('smtp_hostname', smtp_hostname);
			data.append('smtp_port', smtp_port);
			data.append('smtp_secure', smtp_secure);
			data.append('smtp_username', smtp_username);
			data.append('smtp_password', smtp_password);
			data.append('sender_email', sender_email);
			data.append('sender_name', sender_name);

			booknetic.ajax('email_settings.save_settings', data, function()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		});

		if( $("#input_mail_gateway").val() != 'smtp' )
		{
			$(".smtp_details").hide();
		}

		$("#input_mail_gateway, #input_smtp_secure").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select'),
			allowClear: true,
			minimumResultsForSearch: -1
		});

	});

})(jQuery);