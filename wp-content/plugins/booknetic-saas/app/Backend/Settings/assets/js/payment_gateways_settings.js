(function ($)
{
	"use strict";

	$(document).ready(function ()
	{

		$('#booknetic_settings_area').on('click', '.settings-save-btn', function()
		{
			var paypal_enable				        = $('#enable_gateway_paypal').is(':checked') ? 'on' : 'off',
				stripe_enable				        = $('#enable_gateway_stripe').is(':checked') ? 'on' : 'off',
				woocommerce_enable			        = $('#enable_gateway_woocommerce').is(':checked') ? 'on' : 'off',

				paypal_client_id			        = $("#input_paypal_client_id").val(),
				paypal_client_secret		        = $("#input_paypal_client_secret").val(),
				paypal_webhook_id		            = $("#input_paypal_webhook_id").val(),
				paypal_mode					        = $("#input_paypal_mode").val(),

				stripe_client_id			        = $("#input_stripe_client_id").val(),
				stripe_client_secret		        = $("#input_stripe_client_secret").val(),
				stripe_webhook_secret		        = $("#input_stripe_webhook_secret").val(),

				woocommerce_tenant_rediret_to       = $('#input_woocommerce_tenant_rediret_to').val(),
				woocommerce_tenant_order_statuses   = $('#input_woocommerce_tenant_order_statuses').val().join(','),

				payment_gateways_order	            = [];


			$('.step_elements_list > .step_element').each(function()
			{
				payment_gateways_order.push( $(this).data('step-id') );
			});

			booknetic.ajax('save_payment_gateways_settings', {
				paypal_enable: paypal_enable,
				stripe_enable: stripe_enable,
				woocommerce_enable: woocommerce_enable,

				paypal_client_id: paypal_client_id,
				paypal_client_secret: paypal_client_secret,
				paypal_webhook_id: paypal_webhook_id,
				paypal_mode: paypal_mode,

				stripe_client_id: stripe_client_id,
				stripe_client_secret: stripe_client_secret,
				stripe_webhook_secret: stripe_webhook_secret,

				woocommerce_tenant_rediret_to: woocommerce_tenant_rediret_to,
				woocommerce_tenant_order_statuses: woocommerce_tenant_order_statuses,

				payment_gateways_order: JSON.stringify(payment_gateways_order)
			}, function ()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});

		}).on('click', '.step_element:not(.selected_step)', function ()
		{
			$('.step_elements_list > .selected_step .drag_drop_helper > img').attr('src', assetsUrl + 'icons/drag-default.svg');

			$('.step_elements_list > .selected_step').removeClass('selected_step');
			$(this).addClass('selected_step');

			$(this).find('.drag_drop_helper > img').attr('src', assetsUrl + 'icons/drag-color.svg')

			var step_id = $(this).data('step-id');

			$('#booking_panel_settings_per_step > [data-step]').hide();
			$('#booking_panel_settings_per_step > [data-step="'+step_id+'"]').removeClass('hidden').show();
		});

		$( '.step_elements_list' ).sortable({
			placeholder: "step_element selected_step",
			axis: 'y',
			handle: ".drag_drop_helper"
		});

		$("#input_woocommerce_tenant_order_statuses").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select_services'),
			allowClear: true
		});

		$('.step_elements_list > .step_element:eq(0)').trigger('click');

	});

})(jQuery);