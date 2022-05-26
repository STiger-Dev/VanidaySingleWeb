(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		let plan_id, payment_cycle;

		$(document).on('click', '.payment_cycle:not(.active_payment_cycle)', function ()
		{
			$('#input_payment_cycle_swicher').click();
		}).on('click', '#upgrade_plan_btn', function ()
		{
			$('#choose_plan_window').fadeIn(300);
		}).on('click', '.close_choose_plan_window_btn', function ()
		{
			$('#choose_plan_window').fadeOut(300);
		}).on('change', '#input_payment_cycle_swicher', function ()
		{
			if( $(this).is(':checked') )
			{
				$('[data-price="annually"]').removeClass('hidden').show();
				$('[data-price="monthly"]').hide();

				$('.payment_cycle:eq(0)').removeClass('active_payment_cycle');
				$('.payment_cycle:eq(1)').addClass('active_payment_cycle');
			}
			else
			{
				$('[data-price="monthly"]').removeClass('hidden').show();
				$('[data-price="annually"]').hide();

				$('.payment_cycle:eq(0)').addClass('active_payment_cycle');
				$('.payment_cycle:eq(1)').removeClass('active_payment_cycle');
			}
		}).on('click', '.choose_plan_btn', function ()
		{
			plan_id = $(this).closest('.plan_card').data('plan-id');
			payment_cycle = $('#input_payment_cycle_swicher').is(':checked') ? 'annually' : 'monthly';

			$('#chosen_plan_name').text( $(this).closest('.plan_card').find('.plan_title').text().trim() );

			$('#choose_payment_method_window').fadeIn(300, function ()
			{
				$('#choose_plan_window').fadeOut(300);
			});

		}).on('click', '.close_choose_payment_method_window_btn', function()
		{
			$('#choose_payment_method_window').fadeOut(300);
		}).on('click', '.choose_payment_method_back_btn', function()
		{
			$('#choose_plan_window').show();
			$('#choose_payment_method_window').fadeOut(300);
		}).on('click', '.payment_method_card', function ()
		{
			let payment_method = $(this).data('payment-method');

			booknetic.ajax('create_invoice', {
				plan_id: plan_id,
				payment_cycle: payment_cycle,
				payment_method: payment_method
			}, function ( result )
			{
				if( 'url' in result )
				{
					booknetic.loading(1);
					window.location.href = result['url'];
				}
				else if( 'id' in result )
				{
					if( stripe_client_id == '' )
					{
						booknetic.toast( 'Set up Stripe API for using this payment!', 'unsuccess' );
						return;
					}

					Stripe( stripe_client_id ).redirectToCheckout({sessionId: result['id']});
				}
			});
		}).on('click', '.close_payment_succeeded_popup', function ()
		{
			$('#payment_succeeded_popup').fadeOut(300);
		}).on('click', '.close_payment_canceled_popup', function ()
		{
			$('#payment_canceled_popup').fadeOut(300);
		}).on('click', '#cancel_subscription_btn', function ()
		{
			booknetic.confirm( booknetic.__( 'cancel_subscription_text' ), 'danger', '', function ()
			{
				booknetic.ajax( 'cancel_subscription', {}, function ()
				{
					location.reload();
				});
			}, booknetic.__( 'YES' ), booknetic.__( 'NO' ) );
		}).on('click' , '#current_plan_btn' , function ()
		{
			booknetic.loadModal('billing.get_current_plan' , {} , {type:'center'})
		});

		$('#input_payment_cycle_swicher').trigger('change');

		if( window.location.href.indexOf('&upgrade=1') > -1 )
		{
			$('#upgrade_plan_btn').click();
		}

	});

})(jQuery);