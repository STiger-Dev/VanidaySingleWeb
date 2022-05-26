(function ($)
{
	"use strict";

	$(document).ready(function ()
	{

		$('#booknetic_settings_area').on('click', '.settings-save-btn', function()
		{
			let sign_in_page	                = $("#input_sign_in_page").val(),
				sign_up_page	                = $("#input_sign_up_page").val(),
				booking_page	                = $("#input_booking_page").val(),
				forgot_password_page	        = $("#input_forgot_password_page").val(),
				google_maps_api_key				= $("#input_google_maps_api_key").val(),
				google_recaptcha				= $("#input_google_recaptcha").is(':checked')?'on':'off',
				google_recaptcha_site_key		= $("#input_google_recaptcha_site_key").val(),
				confirmation_number				= $('#input_confirmation_number').val(),
				google_recaptcha_secret_key		= $("#input_google_recaptcha_secret_key").val(),
				trial_plan_id		            = $("#input_trial_plan_id").val(),
				expire_plan_id		            = $("#input_expire_plan_id").val(),
				trial_period		            = $("#input_trial_period").val(),
				enable_language_switcher		= $("#input_enable_language_switcher").is(':checked')?'on':'off',
				active_languages		        = $("#input_active_languages").val(),
				new_wp_user_on_new_booking 		= $("#input_new_wp_user_on_new_booking").is(':checked')?'on':'off';

			booknetic.ajax('save_general_settings', {
				sign_in_page: sign_in_page,
				sign_up_page: sign_up_page,
				booking_page: booking_page,
				forgot_password_page: forgot_password_page,
				google_maps_api_key: google_maps_api_key,
				google_recaptcha: google_recaptcha,
				google_recaptcha_site_key: google_recaptcha_site_key,
				google_recaptcha_secret_key: google_recaptcha_secret_key,
				confirmation_number: confirmation_number,
				trial_plan_id: trial_plan_id,
				expire_plan_id: expire_plan_id,
				trial_period: trial_period,
				enable_language_switcher: enable_language_switcher,
				active_languages: active_languages,
				new_wp_user_on_new_booking: new_wp_user_on_new_booking
			}, function ()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});

		});

		$("#input_sign_in_page, #input_sign_up_page").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select'),
			allowClear: true
		});

		var fadeSpeed = 0;
		$('#input_google_recaptcha').on('change', function ()
		{
			if( $(this).is(':checked') )
			{
				$('div[data-hide-key="recaptcha"]').fadeIn(fadeSpeed);
			}
			else
			{
				$('div[data-hide-key="recaptcha"]').fadeOut(fadeSpeed);
			}
		}).trigger('change');
		fadeSpeed = 200;

		var fadeSpeed2 = 0;
		$('#input_enable_language_switcher').on('change', function ()
		{
			if( $(this).is(':checked') )
			{
				$('div[data-hide-key="enable_language_switcher"]').fadeIn(fadeSpeed2);
			}
			else
			{
				$('div[data-hide-key="enable_language_switcher"]').fadeOut(fadeSpeed2);
			}
		}).trigger('change');
		fadeSpeed2 = 200;

		let selected_languages = $('.languagepicker').data('value');
		selected_languages.forEach(function( val )
		{
			$('.languagepicker option[value="'+val+'"]').attr('selected', 'true');
		});

		$('.languagepicker').selectpicker({
			liveSearch: true,
			selectedTextFormat: 'count > 4',
			styleBase: 'form-control',
			style: ''
		});
	});

})(jQuery);
