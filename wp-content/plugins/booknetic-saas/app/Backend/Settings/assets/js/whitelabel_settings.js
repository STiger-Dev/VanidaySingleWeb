(function ($)
{
	"use strict";

	$(document).ready(function ()
	{

		$('#booknetic_settings_area').on('click', '#whitelabel_logo_img', function ()
		{
			$('#whitelabel_logo_input').click();
		}).on('change', '#whitelabel_logo_input', function ()
		{
			if( $(this)[0].files && $(this)[0].files[0] )
			{
				var reader = new FileReader();

				reader.onload = function(e)
				{
					$('#whitelabel_logo_img').attr('src', e.target.result);
				}

				reader.readAsDataURL( $(this)[0].files[0] );
			}
		}).on('click', '#whitelabel_logo_sm_img', function ()
		{
			$('#whitelabel_logo_sm_input').click();
		}).on('change', '#whitelabel_logo_sm_input', function ()
		{
			if( $(this)[0].files && $(this)[0].files[0] )
			{
				var reader = new FileReader();

				reader.onload = function(e)
				{
					$('#whitelabel_logo_sm_img').attr('src', e.target.result);
				}

				reader.readAsDataURL( $(this)[0].files[0] );
			}
		}).on('click', '.settings-save-btn', function()
		{
			var backend_title		= $("#input_backend_title").val(),
			    backend_slug		= $("#input_backend_slug").val(),
				documentation_url	= $("#input_documentation_url").val(),
				powered_by		    = $("#input_powered_by").val(),
				whitelabel_logo_sm	= $("#whitelabel_logo_sm_input")[0].files[0],
				whitelabel_logo		= $("#whitelabel_logo_input")[0].files[0];

			var data = new FormData();

			data.append('backend_title', backend_title);
			data.append('backend_slug', backend_slug);
			data.append('documentation_url', documentation_url);
			data.append('powered_by', powered_by);
			data.append('whitelabel_logo', whitelabel_logo);
			data.append('whitelabel_logo_sm', whitelabel_logo_sm);

			booknetic.ajax('save_whitelabel_settings', data, function()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		});

	});

})(jQuery);
