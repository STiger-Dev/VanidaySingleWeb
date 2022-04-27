(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.select2Ajax( $('.fs-modal .break_line:not(:eq(-1)) .break_start, .fs-modal .break_line:not(:eq(-1)) .break_end, .fs-modal .special-day-row:not(:eq(-1)) .input_special_day_start, .fs-modal .special-day-row:not(:eq(-1)) .input_special_day_end'), 'get_available_times_all');

		booknetic.select2Ajax( $('#input_duration'), 'get_times_with_format', function ()
		{
			return {
				exclude_zero: true
			};
		});
		booknetic.select2Ajax( $('#input_buffer_before, #input_buffer_after, #input_extra_duration'), 'get_times_with_format');

		booknetic.select2Ajax( $('#input_timesheet_1_start, #input_timesheet_2_start, #input_timesheet_3_start, #input_timesheet_4_start, #input_timesheet_5_start, #input_timesheet_6_start, #input_timesheet_7_start, #input_timesheet_1_end, #input_timesheet_2_end, #input_timesheet_3_end, #input_timesheet_4_end, #input_timesheet_5_end, #input_timesheet_6_end, #input_timesheet_7_end'), 'get_available_times_all');

		$('.fs-modal').on('click', '#new_extra_btn', function ()
		{

			$("#extra_list_area").fadeOut(200, function()
			{
				$("#new_extra_panel").fadeIn(200);
			});

			$("#new_extra_btn").fadeOut(200);

			$("#new_extra_panel").data('id', '0');

			// clear old data
			$('#input_extra_name').val('');
			$('#input_extra_price').val('');
			$('#input_extra_price').val('');
			$('#input_extra_duration').select2('val', false);
			$('#input_extra_max_quantity').val('0');
			$('#input_image2').val('');
			$('#new_extra_panel .img-circle1 > img').attr('src', $('#new_extra_panel .img-circle1 > img').data('src'));
			$('#input_extra_hide_price:checked').click();
			$('#input_extra_hide_duration:checked').click();

		}).on('click', '.new_extra_panel_cancel_btn', function ()
		{

			$("#new_extra_panel").fadeOut(200, function()
			{
				$("#extra_list_area").fadeIn(200);
				$("#new_extra_btn").fadeIn(200);
			});

		}).on('click', '.new_extra_panel_save_btn', function ()
		{
			var extra_id		= $(".fs-modal #new_extra_panel").data('id'),
				name			= $(".fs-modal #input_extra_name").val(),
				duration		= $(".fs-modal #input_extra_duration").val(),
				hide_duration	= $(".fs-modal #input_extra_hide_duration").is(':checked')?1:0,
				price			= $(".fs-modal #input_extra_price").val(),
				hide_price		= $(".fs-modal #input_extra_hide_price").is(':checked')?1:0,
				max_quantity	= $(".fs-modal #input_extra_max_quantity").val(),
				image			= $(".fs-modal #input_image2")[0].files[0];

			var data = new FormData();

			data.append('id', extra_id);
			data.append('service_id', $("#add_new_JS").data('service-id'));
			data.append('name', name);
			data.append('duration', duration);
			data.append('hide_duration', hide_duration);
			data.append('price', price);
			data.append('hide_price', hide_price);
			data.append('max_quantity', max_quantity);
			data.append('image', image);

			booknetic.ajax( 'save_extra' , data, function(result )
			{
				var newId = result['id'];

				if( extra_id > 0 )
				{
					var row_that_data_must_change = $(".fs-modal #extra_list_area").children('.extra_row[data-id="' + extra_id + '"]');
				}
				else
				{
					var extraTpl = $(".fs-modal .extra_row:eq(-1)")[0].outerHTML;

					if( $(".fs-modal #extra_list_area > .extra_row").length > 0 )
					{
						$(".fs-modal #extra_list_area > .extra_row:eq(-1)").after( extraTpl );
					}
					else
					{
						$(".fs-modal #extra_list_area").prepend( extraTpl );
					}

					var row_that_data_must_change = $(".fs-modal #extra_list_area").children('.extra_row:eq(-1)');
					row_that_data_must_change.attr('data-id', newId);
				}

				row_that_data_must_change.hide();
				row_that_data_must_change.find('[data-tag="name"]').text( name );
				row_that_data_must_change.find('[data-tag="duration"]').text( result['duration'] );
				row_that_data_must_change.find('[data-tag="price"]').text( result['price'] );
				row_that_data_must_change.find('[data-tag="max_quantity"]').text( max_quantity );

				$(".fs-modal #new_extra_panel").fadeOut(200, function()
				{
					row_that_data_must_change.fadeIn(300);
					$(".fs-modal #extra_list_area").fadeIn(200);
					$(".fs-modal #new_extra_btn").fadeIn(400);
				});

				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		}).on('click', '.delete_extra', function()
		{
			var extraRow	= $(this).closest('.extra_row'),
				extraId		= extraRow.data('id');

			booknetic.confirm(booknetic.__('delete_service_extra'), 'danger', 'trash', function()
			{
				booknetic.ajax('delete_extra', {id: extraId}, function ()
				{
					extraRow.slideUp(200, function()
					{
						$(this).remove();
					});
				});
			});
		}).on('click', '.copy_to_parent_services', function()
		{
			var extraRow	= $(this).closest('.extra_row'),
				extraId		= extraRow.data('id');

			booknetic.ajax('copy_extras', {val: 1, extraId: extraId}, function(res)
			{
				booknetic.toast(res.msg);
			});
			
		}).on('click', '.copy_to_all_services', function()
		{
			var extraRow	= $(this).closest('.extra_row'),
				extraId		= extraRow.data('id');

			booknetic.ajax('copy_extras', {val: 0, extraId: extraId}, function(res)
			{
				booknetic.toast(res.msg);
			});
			
		}).on('click', '.hide_extra', function()
		{
			var extraRow	= $(this).closest('.extra_row'),
				status      = extraRow.attr('data-active') == 1 ? 0 : 1,
				extraId		= extraRow.data('id');

			booknetic.ajax('hide_extra', {id: extraId, status: status}, function ()
			{
				extraRow.attr('data-active', status);
				if( status == 1 )
				{
					extraRow.find('.hide_extra').attr('src', extraRow.find('.hide_extra').attr('src').replace(/view\.svg$/, 'hide.svg'));
				}
				else
				{
					extraRow.find('.hide_extra').attr('src', extraRow.find('.hide_extra').attr('src').replace(/hide\.svg$/, 'view.svg'));
				}
			});
		}).on('click', '.edit_extra', function()
		{
			var extraRow	= $(this).closest('.extra_row'),
				extraId		= extraRow.data('id');

			booknetic.ajax('get_extra_data', {id: extraId}, function (result )
			{
				$(".fs-modal #extra_list_area").fadeOut(200, function()
				{
					$(".fs-modal #new_extra_panel").fadeIn(200);
				});

				$(".fs-modal #new_extra_btn").fadeOut(200);

				$(".fs-modal #new_extra_panel").data('id', extraId);
				$(".fs-modal #new_extra_panel #input_extra_name").val( result['name'] );
				$(".fs-modal #new_extra_panel #input_extra_price").val( result['price'] );
				$(".fs-modal #new_extra_panel #input_extra_duration").append('<option value="' + result['duration']+'">'+ result['duration_txt']+'</option>').val( result['duration'] ).trigger('change');
				$(".fs-modal #new_extra_panel #input_extra_max_quantity").val( result['max_quantity'] );

				$('.fs-modal #new_extra_panel #input_extra_hide_duration').prop( 'checked', (result['hide_duration'] == 1) ).trigger('change');
				$('.fs-modal #new_extra_panel #input_extra_hide_price').prop( 'checked', (result['hide_price'] == 1) ).trigger('change');

				$(".fs-modal .extra_picture img").attr('src' , result['image']);
			});
		}).on('click', '.timesheet_tabs > div', function()
		{
			var type = $(this).data('type');

			if( $(this).hasClass('selected-tab') )
				return;

			$(".fs-modal .timesheet_tabs > .selected-tab").removeClass('selected-tab');

			$(this).addClass('selected-tab');

			$(".fs-modal #tab_timesheet [data-tstab]").hide();
			$(".fs-modal #tab_timesheet [data-tstab='" + type + "']").removeClass('hidden').hide().fadeIn(200);
		}).on('click', '.copy_time_to_all', function()
		{
			var start	 = $(".fs-modal #input_timesheet_1_start").val(),
				end		 = $(".fs-modal #input_timesheet_1_end").val(),
				dayOff	 = $(".fs-modal #dayy_off_checkbox_1").is(':checked'),
				breaks	 = $(".fs-modal .breaks_area[data-day='1'] .break_line"),
				breakTpl = $(".fs-modal .break_line:eq(-1)")[0].outerHTML;

			for(var i = 2; i <=7; i++)
			{
				$(".fs-modal #input_timesheet_"+i+"_start").append( '<option>' + start + '</option>' ).val( start ).trigger('change');
				$(".fs-modal #input_timesheet_"+i+"_end").append( '<option>' + end + '</option>' ).val( end ).trigger('change');
				$(".fs-modal .breaks_area[data-day='"+i+"']").html('');

				breaks.each( function ( index ) {
					let breakStartVal 	= $(this).find('.break_start').val();
					let breakEndVal 	= $(this).find('.break_end').val();
					$(".breaks_area[data-day='"+i+"']").append( breakTpl );
					booknetic.select2Ajax( $(".breaks_area[data-day='"+i+"']").find('.break_start') , 'get_available_times_all' );
					booknetic.select2Ajax( $(".breaks_area[data-day='"+i+"']").find('.break_end') , 'get_available_times_all' );
					$(".breaks_area[data-day='"+i+"'] .break_start:eq("+ index +")").append('<option>' + breakStartVal + '</option>').val( breakStartVal ).trigger('change');
					$(".breaks_area[data-day='"+i+"'] .break_end:eq("+ index +")").append('<option>' + breakEndVal + '</option>').val( breakEndVal ).trigger('change');
				});

				$(".fs-modal .breaks_area[data-day='"+i+"'] .break_line").removeClass('hidden');
				$(".fs-modal #dayy_off_checkbox_"+i).prop('checked', dayOff).trigger('change');
			}
		}).on('click', '#addServiceForm .delete-break-btn', function ()
		{
			$(this).closest('.break_line').slideUp(200, function()
			{
				$(this).remove();
			});
		}).on('change', '#tab_staff .change_price_checkbox', function ()
		{
			if( $(this).is(':checked') )
			{
				$(this).closest('.form-group').next().removeClass('hidden').fadeIn(200);
			}
			else
			{
				$(this).closest('.form-group').next().fadeOut(200);
			}
		}).on('click', '#tab_staff .delete-employee-btn', function()
		{
			$(this).closest('.form-row').slideUp(200, function()
			{
				$(this).remove();
			});
		}).on('click', '#tab_staff .add-employee-btn', function()
		{
			var employeeCount = $(".fs-modal #tab_staff > .staff_list_area > .employee-tpl").length;

			if( employeeCount >= $("#add_new_JS").data('staff-count') )
			{
				booknetic.toast(booknetic.__('no_more_staff_exist'), 'unsuccess');
				return;
			}

			var employeeTpl = $(".fs-modal .employee-tpl:eq(-1)")[0].outerHTML.replace(/change_price_checkbox_[0-9]/g, 'change_price_checkbox_' + (++startCount));

			$(".fs-modal #tab_staff > .staff_list_area").append( employeeTpl );
			$(".fs-modal #tab_staff > .staff_list_area").find(' > .employee-tpl:eq(-1)').removeClass('hidden').hide().slideDown(200);
		}).on('change', '.dayy_off_checkbox', function ()
		{
			$(this).closest('.form-group').prev().find('select').attr( 'disabled', $(this).is(':checked') );

			if( $(this).is(':checked') )
			{
				$(this).closest('.form-row').next('.breaks_area').slideUp( 200 ).next('.add-break-btn').slideUp(200);
			}
			else
			{
				$(this).closest('.form-row').next('.breaks_area').slideDown( 200 ).next('.add-break-btn').slideDown(200);
			}
		}).on('click', '.add-break-btn', function ()
		{
			var area = $(this).prev('.breaks_area');
			var breakTpl = $(".fs-modal .break_line:eq(-1)")[0].outerHTML;

			area.append( breakTpl );
			area.find(' > .break_line:eq(-1)').removeClass('hidden').hide().slideDown(200);

			booknetic.select2Ajax( area.find(' > .break_line:eq(-1) .break_start'), 'get_available_times_all');
			booknetic.select2Ajax( area.find(' > .break_line:eq(-1) .break_end'), 'get_available_times_all');
		}).on('click', '.add-special-day-btn', function ()
		{
			var specialDayTpl = $(".fs-modal .special-day-row:eq(-1)")[0].outerHTML;

			$(".fs-modal .special-days-area").append( specialDayTpl );

			var lastRow = $(".fs-modal .special-days-area > .special-day-row:last");

			var date_format_js = lastRow.find('.input_special_day_date').data('date-format').replace('Y','yyyy').replace('m','mm').replace('d','dd');


			lastRow.find('.input_special_day_date').datepicker({
				autoclose: true,
				format: date_format_js,
				weekStart: weekStartsOn == 'sunday' ? 0 : 1
			});

			booknetic.select2Ajax( lastRow.find('.input_special_day_start'), 'get_available_times_all');
			booknetic.select2Ajax( lastRow.find('.input_special_day_end'), 'get_available_times_all');

			lastRow.removeClass('hidden').hide().slideDown(300);
		}).on('click', '.remove-special-day-btn', function ()
		{
			var spRow = $(this).closest('.special-day-row');
			booknetic.confirm( booknetic.__('delete_special_day'), 'danger', 'unsuccess', function()
			{
				spRow.slideUp(300, function()
				{
					spRow.remove();
				});
			});
		}).on('click', '.special-day-add-break-btn', function()
		{
			var area = $(this).closest('.special-day-row').find('.special_day_breaks_area');
			var breakTpl = $(".fs-modal .break_line:eq(-1)")[0].outerHTML;

			area.append( breakTpl );
			area.find(' > .break_line:eq(-1)').removeClass('hidden').hide().slideDown(200);

			booknetic.select2Ajax( area.find(' > .break_line:eq(-1) .break_start'), 'get_available_times_all');
			booknetic.select2Ajax( area.find(' > .break_line:eq(-1) .break_end'), 'get_available_times_all');
		}).on('change', '#repeatable_checkbox', function ()
		{

			if( $(this).is(':checked') )
			{
				$(".fs-modal [data-for='repeat']").slideDown( $(this).data('slideSpeed') || 0 );
			}
			else
			{
				$(".fs-modal [data-for='repeat']").slideUp( $(this).data('slideSpeed') || 0 );
			}

			$(this).data('slideSpeed', 200);

		}).on('change', '#deposit_checkbox', function ()
		{

			if( $(this).is(':checked') )
			{
				$(".fs-modal [data-for='deposit']").slideDown( $(this).data('slideSpeed') || 0 );
			}
			else
			{
				$(".fs-modal [data-for='deposit']").slideUp( $(this).data('slideSpeed') || 0 );
			}

			$(this).data('slideSpeed', 200);

		}).on('change', '#group_booking_checkbox', function ()
		{

			if( $(this).is(':checked') )
			{
				$(".fs-modal #group_booking_area").slideDown(200);
			}
			else
			{
				$(".fs-modal #group_booking_area").slideUp(200);

			}

		}).on('change', '#recurring_fixed_full_period, #recurring_fixed_frequency', function ()
		{

			if( $(this).is(':checked') )
			{
				$(this).closest('.form-group').next().fadeIn(200);
			}
			else
			{
				$(this).closest('.form-group').next().fadeOut(200);
			}

		}).on('change', '#input_recurring_type', function ()
		{
			var selectedType = $(this).val();

			var text = '';
			switch( selectedType )
			{
				case 'monthly':
					text = booknetic.__('times_per_month');
					break;
				case 'weekly':
					text = booknetic.__('times_per_week');
					break;
				case 'daily':
					text = booknetic.__('every_n_day');
					break;
			}

			$(".fs-modal .repeat_frequency_txt").text( text );

		}).on('click', '#addServiceSave', function ()
		{
			var name					= $(".fs-modal #input_name").val(),
				category				= $(".fs-modal #input_category").val(),

				duration				= $(".fs-modal #input_duration").val(),
				timeslot_length			= $(".fs-modal #input_time_slot_length").val(),

				price					= $(".fs-modal #input_price").val(),
				deposit_enabled 		= $("#deposit_checkbox").is(':checked') ? 1 : 0,
				deposit					= $(".fs-modal #input_deposit").val(),
				deposit_type			= $(".fs-modal #input_deposit_type").val(),
				hide_price			    = $(".fs-modal #input_hide_price").is(':checked') ? 1 : 0,
				hide_duration			= $(".fs-modal #input_hide_duration").is(':checked') ? 1 : 0,

				buffer_before			= $(".fs-modal #input_buffer_before").val(),
				buffer_after			= $(".fs-modal #input_buffer_after").val(),

				repeatable				= $(".fs-modal #repeatable_checkbox").is(':checked') ? 1 : 0,

				fixed_full_period		= $(".fs-modal #recurring_fixed_full_period").is(':checked') ? 1 : 0,
				full_period				= !fixed_full_period ? '' : $(".fs-modal #input_full_period").val(),
				full_period_type		= !fixed_full_period ? '' : $(".fs-modal #input_full_period_type").val(),

				repeat_type				= $(".fs-modal #input_recurring_type").val( ),
				recurring_payment_type	= $(".fs-modal #input_recurring_payment_type").val( ),

				fixed_frequency			= $(".fs-modal #recurring_fixed_frequency").is(':checked') ? 1 : 0,
				repeat_frequency		= !fixed_frequency ? '' : $(".fs-modal #input_repeat_frequency").val(),

				capacity				= $(".fs-modal #select_capacity").val(),
				max_capacity			= capacity === '0' ? 1 : $(".fs-modal #input_max_capacity").val(),

				employees				= [],
				note					= $(".fs-modal #input_note").val(),
				image					= $(".fs-modal #input_image")[0].files[0],
				color					= $(".fs-modal .service_color").data('color'),
				extras					= [];

			if( name === '' || category === '' || duration === '' || price === '' || deposit === '' )
			{
				booknetic.toast(booknetic.__('fill_all_required'), 'unsuccess');
				return;
			}

			var weekly_schedule = [ ];

			if( $('#set_specific_timesheet_checkbox').is(':checked') )
			{
				for( var d=1; d <= 7; d++)
				{
					(function()
					{
						var dayOff	= $(".fs-modal #dayy_off_checkbox_"+d).is(':checked') ? 1 : 0,
							start	= dayOff ? '' : $(".fs-modal #input_timesheet_"+d+"_start").val(),
							end		= dayOff ? '' : $(".fs-modal #input_timesheet_"+d+"_end").val(),
							breaks	= [];

						if( !dayOff )
						{
							$(".fs-modal .breaks_area[data-day='" + d + "'] > .break_line").each(function()
							{
								var breakStart	= $(this).find('.break_start').val(),
									breakEnd	= $(this).find('.break_end').val();

								if( breakStart != '' && breakEnd != '' )
									breaks.push( [ breakStart, breakEnd ] );
							});
						}

						weekly_schedule.push( {
							'start'		: start,
							'end'		: end,
							'day_off'	: dayOff,
							'breaks'	: breaks
						} );
					})();
				}
			}

			var special_days = [];
			$(".fs-modal .special-days-area > .special-day-row").each(function ()
			{
				var spId = $(this).data('id'),
					spDate = $(this).find('.input_special_day_date').val(),
					spStartTime = $(this).find('.input_special_day_start').val(),
					spEndTime = $(this).find('.input_special_day_end').val(),
					spBreaks = [];

				$(this).find('.special_day_breaks_area > .break_line').each(function()
				{
					var breakStart = $(this).find('.break_start').val(),
						breakEnd = $(this).find('.break_end').val();

					spBreaks.push([ breakStart, breakEnd ]);
				});

				special_days.push({
					'id': spId > 0 ? spId : 0,
					'date': spDate,
					'start': spStartTime,
					'end': spEndTime,
					'breaks': spBreaks
				});
			});


			$(".fs-modal .staff_list_area > .employee-tpl").each(function()
			{
				var employeeId			= $(this).find('select.employee_select').val(),
					priceIsStandart		= $(this).find('.change_price_checkbox').is(':checked') ? 0 : 1,
					employeePrice		= priceIsStandart ? -1 : $(this).find('.except_price_input').val(),
					employeeDeposit		= priceIsStandart ? -1 : $(this).find('.except_deposit_input').val(),
					employeeDepositType	= $(this).find('.except_deposit_type_input').val();

				employees.push([ employeeId, employeePrice, employeeDeposit, employeeDepositType ]);
			});

			$("#extra_list_area > .extra_row").each(function()
			{
				extras.push( $(this).data('id') )
			});

			var data = new FormData();

			data.append('id', $("#add_new_JS").data('service-id') );
			data.append('name', name);
			data.append('category', category);
			data.append('duration', duration);
			data.append('timeslot_length', timeslot_length);

			data.append('price', price);
			data.append('deposit_enabled', deposit_enabled);
			data.append('deposit', deposit);
			data.append('deposit_type', deposit_type);
			data.append('hide_price', hide_price);
			data.append('hide_duration', hide_duration);

			data.append('buffer_before', buffer_before);
			data.append('buffer_after', buffer_after);

			data.append('repeatable', repeatable);

			data.append('fixed_full_period', fixed_full_period);
			data.append('full_period_value', full_period);
			data.append('full_period_type', full_period_type);

			data.append('repeat_type', repeat_type);
			data.append('recurring_payment_type', recurring_payment_type);

			data.append('fixed_frequency', fixed_frequency);
			data.append('repeat_frequency', repeat_frequency);

			data.append('max_capacity', max_capacity);

			data.append('employees', JSON.stringify( employees ));
			data.append('note', note);
			data.append('image', image);
			data.append('color', color);

			data.append('weekly_schedule', JSON.stringify( weekly_schedule ));
			data.append('special_days', JSON.stringify( special_days ));
			data.append('extras', JSON.stringify( extras ));

			data.append( 'custom_payment_methods_enabled', $( '#service_settings_custom_payment_methods_enabled' ).is(':checked') ? 1 : 0 );
			data.append( 'custom_payment_methods', $( '#service_settings_custom_payment_methods' ).val() );

			booknetic.ajax('save_service', data, function()
			{
				booknetic.modalHide( $(".fs-modal") );

				location.reload();
			});

		}).on('click', '.service_picture img', function ()
		{
			$('#input_image').click();
		}).on('change', '#input_image', function ()
		{
			if( $(this)[0].files && $(this)[0].files[0] )
			{
				var reader = new FileReader();

				reader.onload = function(e)
				{
					$('.fs-modal .service_picture img').attr('src', e.target.result);
				}

				reader.readAsDataURL( $(this)[0].files[0] );
			}
		}).on('click', '.service_picture > .service_color', function ()
		{
			var x = parseInt( $(".fs-modal .fs-modal-content").outerWidth() ) / 2 - $("#service_color_panel").outerWidth()/2,
				y = parseInt( $(this).offset().top ) + 60;

			$("#service_color_panel").css({top: y+'px', left: x+'px'}).fadeIn(200);
		}).on('click', '.extra_picture img', function ()
		{
			$('#input_image2').click();
		}).on('change', '#input_image2', function ()
		{
			if( $(this)[0].files && $(this)[0].files[0] )
			{
				var reader = new FileReader();

				reader.onload = function(e)
				{
					$('.fs-modal .extra_picture img').attr('src', e.target.result);
				}

				reader.readAsDataURL( $(this)[0].files[0] );
			}
		}).on('click', '#service_color_panel .color-rounded', function ()
		{
			$("#service_color_panel .color-rounded.selected-color").removeClass('selected-color');
			$(this).addClass('selected-color');

			var color = $(this).data('color');

			$("#input_color_hex").val( color );
		}).on('click', '#service_color_panel .close-btn1', function ()
		{
			$("#service_color_panel .close-popover-btn").click();
		}).on('click', '#service_color_panel .save-btn1', function ()
		{
			var color = $("#input_color_hex").val();

			$(".fs-modal .service_color").css('background-color', color).data('color', color);

			$("#service_color_panel .close-popover-btn").click();
		}).on('change', '#set_specific_timesheet_checkbox', function ()
		{
			if( $(this).is(':checked') )
			{
				$('#set_specific_timesheet').slideDown(200);
			}
			else
			{
				$('#set_specific_timesheet').slideUp(200);
			}
		}).on('change', '#select_capacity', function ()
		{
			if( $(this).val() === '0' )
			{
				$(this).parent().next().fadeOut(200);
			}
			else
			{
				$(this).parent().next().fadeIn(200);
			}
		}).on('click', '#hideServiceBtn', function ()
		{
			booknetic.ajax('hide_service', { service_id: $("#add_new_JS").data('service-id') }, function ()
			{
				booknetic.loading(1);
				location.reload();
			});
		}).on( 'change', '#service_settings_custom_payment_methods_enabled', function () {
			if ( $( this ).is( ':checked' ) )
			{
				$( '#serviceCustomPaymentMethodsContainer' ).slideDown( 200 );
			}
			else
			{
				$( '#serviceCustomPaymentMethodsContainer' ).slideUp( 200 );
			}
		} );

		if( $("#add_new_JS").data('service-id') == 0 )
		{
			$('#tab_staff .add-employee-btn').click();
		}

		$( '.fs-modal #service_settings_custom_payment_methods_enabled' ).trigger( 'change' );

		$(".fs-modal #tab_staff .change_price_checkbox").trigger('change');

		$(".fs-modal #deposit_checkbox").trigger('change');

		$(".fs-modal #repeatable_checkbox").trigger('change');

		$(".fs-modal #input_employees, .fs-modal #input_category").select2({
			theme:			'bootstrap',
			placeholder:	booknetic.__('select'),
			allowClear:		true
		});

		$(".fs-modal #group_booking_checkbox").trigger('change');

		$('.fs-modal #recurring_fixed_full_period, .fs-modal #recurring_fixed_frequency').trigger('change');

		$(".fs-modal #input_recurring_type").trigger('change');

		var selectedCategory = $(".service_category.sc-selected").data('id');
		if( selectedCategory > 0 )
		{
			$(".fs-modal #input_category").val( selectedCategory ).trigger('change');
		}

		$(".fs-modal .dayy_off_checkbox").trigger('change');

		$("#input_color_hex").colorpicker({
			format: 'hex'
		});

		$('.fs-modal .service_picture .d-none').removeClass('d-none');

		if( $('.fs-modal #extra_list_area > .extra_row').length == 0 )
		{
			$('#new_extra_btn').click();
		}

		$('#set_specific_timesheet_checkbox').trigger('change');

		$('#select_capacity').trigger('change');

		var date_format_js = $('.fs-modal .input_special_day_date').data('date-format').replace('Y','yyyy').replace('m','mm').replace('d','dd');

		$('.fs-modal .input_special_day_date').datepicker({
			autoclose: true,
			format: date_format_js,
			weekStart: weekStartsOn == 'sunday' ? 0 : 1
		});



		// settings tab

		$( '#service_settings_custom_payment_methods' ).select2( {
			theme:			'bootstrap',
			placeholder:	booknetic.__( 'select' ),
			allowClear:		true
		} );

	});

})(jQuery);