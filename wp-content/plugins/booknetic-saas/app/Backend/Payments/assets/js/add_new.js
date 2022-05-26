(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		let current_modal = $('#addPaymentSave').closest('.fs-modal');

		current_modal.on('click', '#addPaymentSave', function ()
		{
			let tenant_id				= $('#input_tenant_id').val(),
				plan_id	                = $("#input_plan_id").val(),
				amount		            = $("#input_amount").val(),
				payment_method			= $("#input_payment_method").val(),
				payment_cycle			= $("#input_payment_cycle").val(),
				created_at				= $("#input_created_at").val();


			var data = new FormData();

			data.append('id', $(".fs-modal #add_new_JS").data('billing-id'));
			data.append('tenant_id', tenant_id);
			data.append('plan_id', plan_id);
			data.append('amount', amount);
			data.append('payment_method', payment_method);
			data.append('payment_cycle', payment_cycle);
			data.append('created_at', created_at);

			booknetic.ajax( 'save_payment', data, function()
			{
				booknetic.modalHide($(".fs-modal"));

				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		}).on('change', '#input_tenant_id', function()
		{
			if( $(".fs-modal #add_new_JS").data('billing-id') > 0 )
				return false;

			var data = $(this).select2('data');
			var plan_id = data[0]['plan_id'];
			var last_amount = data[0]['last_amount'];
			var last_cycle = data[0]['last_cycle'];

			$('#input_plan_id').val( plan_id ).trigger('change');
			$('#input_amount').val( last_amount );
			$('#input_payment_cycle').val( last_cycle ).trigger('change');

		});


		$('#input_plan_id, #input_payment_method, #input_payment_cycle').select2({
			theme: 'bootstrap',
			allowClear: false
		});

		let time = $("#input_created_at").val().split(' ');

		$("#input_created_at").datepicker({
			autoclose: true,
			format: 'yyyy-mm-dd ' + time[1],
			weekStart: weekStartsOn == 'sunday' ? 0 : 1
		});

		booknetic.select2Ajax( $(".fs-modal #input_tenant_id"), 'get_tenants' );


	});

})(jQuery);