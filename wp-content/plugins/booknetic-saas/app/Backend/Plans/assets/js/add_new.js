(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$('.fs-modal').on('click', '#addPlanSave', function ()
		{
			let name								    = $("#input_name").val(),
				color								    = $("#input_color").val(),
				ribbon_text							    = $("#input_ribbon_text").val(),
				order_by							    = $("#input_order_by").val(),
				monthly_price						    = $("#input_monthly_price").val(),
				monthly_price_discount				    = $("#input_monthly_price_discount").val(),
				annually_price						    = $("#input_annually_price").val(),
				annually_price_discount				    = $("#input_annually_price_discount").val(),
				is_active		                        = $('#input_is_active').is(':checked') ? 'off' : 'on',
				description							    = $("#input_description").summernote('code');

			let data = new FormData();
			let capabilities = {};
			let limits = {};

			$("#tab_capabilities .form-group").each(function ()
			{
				let permissionKey = $(this).find('.fs_onoffswitch-checkbox').attr('id').replace( 'input_permission_', '' );
				let permissionVal = $(this).find('.fs_onoffswitch-checkbox').is(':checked') ? 'on' : 'off';
				capabilities[ permissionKey ] = permissionVal;
			});

			$("#tab_limits .form-group").each(function ()
			{
				let limitKey = $(this).find('.permission-limit').attr('id').replace( 'input_limit_', '' );
				let limitVal = $(this).find('.permission-limit').val();
				limits[ limitKey ] = limitVal;
			})

			data.append( 'id', $(".fs-modal #add_new_JS").data('plan-id') );
			data.append( 'name', name );
			data.append( 'ribbon_text', ribbon_text );
			data.append( 'color', color );
			data.append( 'order_by', order_by );
			data.append( 'monthly_price', monthly_price );
			data.append( 'monthly_price_discount', monthly_price_discount );
			data.append( 'annually_price', annually_price );
			data.append( 'annually_price_discount', annually_price_discount );
			data.append( 'is_active', is_active );
			data.append( 'description', description );
			data.append( 'capabilities', JSON.stringify( capabilities ) );
			data.append( 'limits', JSON.stringify( limits ) );

			booknetic.ajax( 'save_plan', data, function()
			{
				booknetic.modalHide($(".fs-modal"));

				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		}).on('click', '#input_color, .plan_color', function ()
		{
			let x = parseInt( $(".fs-modal .fs-modal-content").outerWidth() ) / 2 - $("#plan_color_panel").outerWidth()/2,
				y = parseInt( $(this).offset().top ) + 60;

			$("#plan_color_panel").css({top: y+'px', left: x+'px'}).fadeIn(200);
		}).on('click', '#plan_color_panel .color-rounded', function ()
		{
			$("#plan_color_panel .color-rounded.selected-color").removeClass('selected-color');
			$(this).addClass('selected-color');

			var color = $(this).data('color');

			$("#input_color_hex").val( color );
		}).on('click', '#plan_color_panel .close-btn1', function ()
		{
			$("#plan_color_panel .close-popover-btn").click();
		}).on('click', '#plan_color_panel .save-btn1', function ()
		{
			let color = $("#input_color_hex").val();

			$(".fs-modal .plan_color").css('background-color', color);
			$('#input_color').val( color );

			$("#plan_color_panel .close-popover-btn").click();
		}).on('change', '.form-groups-list input[type="checkbox"]', function ()
		{
			let parentCapability = $(this).closest('.form-groups-list').prev().find('input');
			if( $(this).is(':checked') && !parentCapability.is(':checked') )
			{
				parentCapability.click();
			}
			else if( parentCapability.is(':checked') && $(this).closest('.form-groups-list').find('input[type="checkbox"]:checked').length == 0 )
			{
				parentCapability.click();
			}
		}).on('change', '#tab_capabilities > .form-group input[type="checkbox"]', function ()
		{
			if( $(this).is(':checked') )
			{
				$(this).closest('.form-group').next('.form-groups-list').find('input:not(:checked)').click();
			}
			else
			{
				$(this).closest('.form-group').next('.form-groups-list').find('input:checked').click();
			}
		});

		let current_color = $('#input_color').val();
		if( current_color !== '' )
		{
			$(".fs-modal .plan_color").css('background-color', current_color);
		}

		$("#input_color_hex").colorpicker({
			format: 'hex'
		});

		$('#input_description').summernote({
			dialogsInBody: true,
			placeholder: '',
			tabsize: 2,
			height: 350,
			toolbar: [
				['style', ['style']],
				['style', ['bold', 'italic', 'underline', 'clear']],
				['fontsize', ['fontsize']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['table', ['table']],
				['insert', ['link', 'picture']],
				['view', ['codeview']],
				['height', ['height']]
			]
		});

	});

})(jQuery);