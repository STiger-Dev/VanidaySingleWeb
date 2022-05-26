(function ($)
{
	"use strict";

	$(document).ready(function ()
	{
		$(document).on('click', '#show_price_calculator', function ()
		{
			if( $('#price_calcualtor_section').hasClass('hidden') )
			{
				$('#price_calcualtor_section').removeClass('hidden').hide();
			}

			$('#price_calcualtor_section').fadeToggle( 300 );
		}).on('click', '#calcualte_btn', function ()
		{
			let payment_cycle = $('#select_payment_cycle').val(),
				price = $('#select_plan :selected').data('price-' + payment_cycle);

			$('#input_add_deposit').val( price );
		}).on('click', '#modal_add_deposit_btn', function ()
		{
			let price = $('#input_add_deposit').val();

			booknetic.ajax('add_deposit_save', {deposit: price}, function ( result )
			{
				if( 'redirect_url' in result )
				{
					location.href = result['redirect_url'];
				}
			});
		});
	});

})(jQuery);