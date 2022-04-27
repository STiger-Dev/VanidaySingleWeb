var bookneticPaymentStatus;

var bookneticHooks = {

	hooks: {},

	addFilter: function ( key, fn, fn_id )
	{
		key = key.toLowerCase();

		if ( ! this.hooks.hasOwnProperty( key ) )
		{
			this.hooks[ key ] = {};
		}

		if (fn === null && this.hooks[key].hasOwnProperty(fn_id)) {
			delete this.hooks[key][fn_id];
			return 0;
		}

		if (fn_id === undefined || fn_id === null) {
			while(true) {
				fn_id = Math.random().toString(36).substring(2, 15);
				if (!this.hooks[key].hasOwnProperty(fn_id))
					break;
			}
		}

		this.hooks[ key ][ fn_id ] = fn;
		return fn_id;
	},

	doFilter: function ( key, params, ...extra )
	{
		key = key.toLowerCase();

		if ( this.hooks.hasOwnProperty( key ) )
		{
			for (let fn_id in this.hooks[key])
			{
				let fn = this.hooks[key][fn_id];
				if ( typeof params === 'undefined' )
				{
					params = fn( ...extra );
				}
				else
				{
					params = fn( params, ...extra );
				}
			};
		}

		return params;
	},

	addAction: function ( key, fn, fn_id )
	{
		return this.addFilter( key, fn, fn_id );
	},

	doAction: function ( key, ...params )
	{
		this.doFilter( key, undefined, ...params );
	}

};

(function($)
{
	"use strict";

	function __( key )
	{
		return key in BookneticData.localization ? BookneticData.localization[ key ] : key;
	}

	$(document).ready( function()
	{
		let index = 0;
		let initBookingPage = function ( value )
		{
			index++;
			let booking_panel_js = $(value);
			let google_recaptcha_token;
			let google_recaptcha_action = 'booknetic_booking_panel_' + index;
			let booknetic = {
				__,

				panel_js : booking_panel_js,

				options: {
					'templates': {
						'loader': '<div class="booknetic_loading_layout"></div>'
					}
				},

				localization: {
					month_names: [ __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December') ],
					day_of_week: [ __('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun') ] ,
				},

				calendarDateTimes: {},
				time_show_format: 1,
				calendarYear: null,
				calendarMonth: null,

				paymentWindow: null,
				paymentStatus: null,
				appointmentId: null,
				uniqueToken: null,
				dateBasedService: false,
				serviceData: null,

				globalDayOffs: {},
				globalTimesheet: {},

				save_step_data: null,


				loading: function ( onOff )
				{
					if( typeof onOff === 'undefined' || onOff )
					{
						$('#booknetic_progress').removeClass('booknetic_progress_done').show();
						$({property: 0}).animate({property: 100}, {
							duration: 1000,
							step: function()
							{
								var _percent = Math.round(this.property);
								if( !$('#booknetic_progress').hasClass('booknetic_progress_done') )
								{
									$('#booknetic_progress').css('width',  _percent+"%");
								}
							}
						});

						$('body').append( this.options.templates.loader );
					}
					else if( ! $('#booknetic_progress').hasClass('booknetic_progress_done') )
					{
						$('#booknetic_progress').addClass('booknetic_progress_done').css('width', 0);

						// IOS bug...
						setTimeout(function ()
						{
							$('.booknetic_loading_layout').remove();
						}, 0);
					}
				},

				htmlspecialchars_decode: function (string, quote_style)
				{
					var optTemp = 0,
						i = 0,
						noquotes = false;
					if(typeof quote_style==='undefined')
					{
						quote_style = 2;
					}
					string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
					var OPTS = {
						'ENT_NOQUOTES': 0,
						'ENT_HTML_QUOTE_SINGLE': 1,
						'ENT_HTML_QUOTE_DOUBLE': 2,
						'ENT_COMPAT': 2,
						'ENT_QUOTES': 3,
						'ENT_IGNORE': 4
					};
					if(quote_style===0)
					{
						noquotes = true;
					}
					if(typeof quote_style !== 'number')
					{
						quote_style = [].concat(quote_style);
						for (i = 0; i < quote_style.length; i++){
							if(OPTS[quote_style[i]]===0){
								noquotes = true;
							} else if(OPTS[quote_style[i]]){
								optTemp = optTemp | OPTS[quote_style[i]];
							}
						}
						quote_style = optTemp;
					}
					if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
					{
						string = string.replace(/&#0*39;/g, "'");
					}
					if(!noquotes){
						string = string.replace(/&quot;/g, '"');
					}
					string = string.replace(/&amp;/g, '&');
					return string;
				},

				htmlspecialchars: function ( string, quote_style, charset, double_encode )
				{
					var optTemp = 0,
						i = 0,
						noquotes = false;
					if(typeof quote_style==='undefined' || quote_style===null)
					{
						quote_style = 2;
					}
					string = typeof string != 'string' ? '' : string;

					string = string.toString();
					if(double_encode !== false){
						string = string.replace(/&/g, '&amp;');
					}
					string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
					var OPTS = {
						'ENT_NOQUOTES': 0,
						'ENT_HTML_QUOTE_SINGLE': 1,
						'ENT_HTML_QUOTE_DOUBLE': 2,
						'ENT_COMPAT': 2,
						'ENT_QUOTES': 3,
						'ENT_IGNORE': 4
					};
					if(quote_style===0)
					{
						noquotes = true;
					}
					if(typeof quote_style !== 'number')
					{
						quote_style = [].concat(quote_style);
						for (i = 0; i < quote_style.length; i++)
						{
							if(OPTS[quote_style[i]]===0)
							{
								noquotes = true;
							}
							else if(OPTS[quote_style[i]])
							{
								optTemp = optTemp | OPTS[quote_style[i]];
							}
						}
						quote_style = optTemp;
					}
					if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
					{
						string = string.replace(/'/g, '&#039;');
					}
					if(!noquotes)
					{
						string = string.replace(/"/g, '&quot;');
					}
					return string;
				},

				formDataToObject: function ( formData )
				{
					var object = {};

					formData.forEach(function(value, key)
					{
						object[key] = value;
					});

					return object;
				},

				ajaxResultCheck: function ( res )
				{

					if( typeof res != 'object' )
					{
						try
						{
							res = JSON.parse(res);
						}
						catch(e)
						{
							this.toast( 'Error!' );
							return false;
						}
					}

					if( typeof res['status'] == 'undefined' )
					{
						this.toast( 'Error!' );
						return false;
					}

					if( res['status'] == 'error' )
					{
						this.toast( typeof res['error_msg'] == 'undefined' ? 'Error!' : res['error_msg'] );
						return false;
					}

					if( res['status'] == 'ok' )
						return true;

					// else

					this.toast( 'Error!' );
					return false;
				},

				ajax: function ( action , params , func , loading, fnOnError, async_param )
				{
					async_param = typeof async_param === 'undefined' ? true : async_param;
					loading     = loading === false ? false : true;

					if( loading )
					{
						booknetic.loading(true);
					}

					if( params instanceof FormData )
					{
						params.append('action', 'bkntc_' + action);
						params.append('tenant_id', BookneticData.tenant_id);
					}
					else
					{
						params['action'] = 'bkntc_' + action;
						params['tenant_id'] = BookneticData.tenant_id;
					}

					bookneticHooks.doAction( 'ajax_before_' + action, params, booknetic );
					params = bookneticHooks.doFilter( 'ajax', params, booknetic );
					params = bookneticHooks.doFilter( 'ajax_' + action, params, booknetic );

					var ajaxObject =
						{
							url: BookneticData.ajax_url,
							method: 'POST',
							data: params,
							async: async_param,
							success: function ( result )
							{
								if( loading )
								{
									booknetic.loading( 0 );
								}

								if( booknetic.ajaxResultCheck( result, fnOnError ) )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}

									if( typeof func == 'function' )
									{
										func( result );

										bookneticHooks.doAction( 'ajax_after_' + action + '_success', booknetic, params, result );
									}
								}
								else if( typeof fnOnError == 'function' )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}

									fnOnError( result );

									bookneticHooks.doAction( 'ajax_after_' + action + '_error', booknetic, params, result );
								}
							},
							error: function (jqXHR, exception)
							{
								if( loading )
								{
									booknetic.loading( 0 );
								}

								booknetic.toast( jqXHR.status + ' error!' );

								if( typeof fnOnError == 'function' )
								{
									fnOnError();

									bookneticHooks.doAction( 'ajax_after_' + action + '_error', booknetic, params, result );
								}
							}
						};

					if( params instanceof FormData)
					{
						ajaxObject['processData'] = false;
						ajaxObject['contentType'] = false;
					}

					$.ajax( ajaxObject );

				},

				select2Ajax: function ( select, action, parameters )
				{
					var params = {};
					params['action'] = 'bkntc_' + action;
					params['tenant_id'] = BookneticData.tenant_id;

					select.select2({
						theme: 'bootstrap',
						placeholder: __('select'),
						language: {
							searching: function() {
								return __('searching');
							}
						},
						allowClear: true,
						ajax: {
							url: BookneticData.ajax_url,
							dataType: 'json',
							type: "POST",
							data: function ( q )
							{
								var sendParams = params;
								sendParams['q'] = q['term'];

								if( typeof parameters == 'function' )
								{
									var additionalParameters = parameters( $(this) );

									for (var key in additionalParameters)
									{
										sendParams[key] = additionalParameters[key];
									}
								}
								else if( typeof parameters == 'object' )
								{
									for (var key in parameters)
									{
										sendParams[key] = parameters[key];
									}
								}

								return sendParams;
							},
							processResults: function ( result )
							{
								if( booknetic.ajaxResultCheck( result ) )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}

									return result;
								}
							}
						}
					});
				},

				zeroPad: function(n, p)
				{
					p = p > 0 ? p : 2;

					n = String(n);
					while (n.length < p)
						n = '0' + n;

					return n;
				},

				toast: function( title )
				{
					if( title === false )
					{
						booking_panel_js.find('.booknetic_warning_message').fadeOut(200);
						return;
					}

					booking_panel_js.find('.booknetic_warning_message').text( booknetic.htmlspecialchars_decode( title, 'ENT_QUOTES' ) ).fadeIn(300);
					setTimeout(function ()
					{
						booking_panel_js.find('.booknetic_warning_message').fadeOut(200);
					}, 5000);
				},

				nonRecurringCalendar: function ( _year , _month, loader, load_dates )
				{
					var now = new Date();
					loader = loader === false ? false : true;

					_month = (typeof _month == 'undefined') ? now.getMonth() : _month;
					_year = (typeof _year == 'undefined') ? now.getFullYear() : _year;

					booknetic.calendarYear = _year;
					booknetic.calendarMonth = _month;

					if( typeof load_dates != 'undefined' && load_dates === false )
					{
						booknetic.displayCalendar( loader );
						booknetic.displayBringPeopleSelect();
					}
					else
					{
						booknetic.ajax( 'get_data_date_time', booknetic.ajaxParameters( {
							year:   _year,
							month:  _month + 1
						} ), function ( result )
						{
							booknetic.calendarDateTimes = result['data'];
							booknetic.time_show_format = result['time_show_format'];
							booknetic.displayCalendar( loader );
							booknetic.displayBringPeopleSelect();
						} , loader );
					}
				},

				displayBringPeopleSelect: function()
				{
					var select = $('.booknetic_number_of_brought_customers select');

					var options = '';

					for(var i = 1; i < booknetic.serviceMaxCapacity; i++ )
					{
						options += '<option value="' + i + '"> + ' + i + '</option>'
					}

					select.html( options );

				},

				displayCalendar: function( loader )
				{
					var _year = booknetic.calendarYear;
					var _month = booknetic.calendarMonth;

					var htmlContent		= "",
						febNumberOfDays	= "",
						counter			= 1,
						dateNow			= new Date(_year , _month ),
						month			= dateNow.getMonth()+1,
						year			= dateNow.getFullYear(),
						currentDate		= new Date();

					if (month == 2)
					{
						febNumberOfDays = ( (year%100!=0) && (year%4==0) || (year%400==0)) ? '29' : '28';
					}

					var monthNames	= booknetic.localization.month_names;
					var dayPerMonth	= [null, '31', febNumberOfDays ,'31','30','31','30','31','31','30','31','30','31']

					var nextDate	= new Date(month +'/01/'+year);
					var weekdays	= nextDate.getDay();
					if( BookneticData.week_starts_on == 'monday' )
					{
						var weekdays2	= weekdays == 0 ? 7 : weekdays;
						var week_start_n = 1;
						var week_end_n = 7;
					}
					else
					{
						var weekdays2	= weekdays;
						var week_start_n = 0;
						var week_end_n = 6;
					}

					var numOfDays	= dayPerMonth[month];

					for( var w=week_start_n; w < weekdays2; w++ )
					{
						htmlContent += "<div class=\"booknetic_td booknetic_empty_day\"></div>";
					}

					while (counter <= numOfDays)
					{
						if (weekdays2 > week_end_n)
						{
							weekdays2 = week_start_n;
							htmlContent += "</div><div class=\"booknetic_calendar_rows\">";
						}
						var date_formatted = year + '-' + booknetic.zeroPad(month) + '-' + booknetic.zeroPad(counter);

						if( BookneticData.date_format == 'Y-m-d' )
						{
							var date_format_view = year + '-' + booknetic.zeroPad(month) + '-' + booknetic.zeroPad(counter);
						}
						else if( BookneticData.date_format == 'd-m-Y' )
						{
							var date_format_view = booknetic.zeroPad(counter) + '-' + booknetic.zeroPad(month) + '-' + year;
						}
						else if( BookneticData.date_format == 'm/d/Y' )
						{
							var date_format_view = booknetic.zeroPad(month) + '/' + booknetic.zeroPad(counter) + '/' + year;
						}
						else if( BookneticData.date_format == 'd/m/Y' )
						{
							var date_format_view = booknetic.zeroPad(counter) + '/' + booknetic.zeroPad(month) + '/' + year;
						}
						else if( BookneticData.date_format == 'd.m.Y' )
						{
							var date_format_view = booknetic.zeroPad(counter) + '.' + booknetic.zeroPad(month) + '.' + year;
						}

						var addClass = '';
						if( !(date_formatted in booknetic.calendarDateTimes['dates']) || booknetic.calendarDateTimes['dates'][ date_formatted ].length == 0 )
						{
							addClass = ' booknetic_calendar_empty_day';
						}

						var loadLine = booknetic.drawLoadLine( date_formatted );

						htmlContent +="<div class=\"booknetic_td booknetic_calendar_days"+addClass+"\" data-date=\"" + date_formatted + "\" data-date-format=\"" + date_format_view + "\"><div>"+counter+"<span>" + loadLine + "</span></div></div>";

						weekdays2++;
						counter++;
					}

					for( var w=weekdays2; w <= week_end_n; w++ )
					{
						htmlContent += "<div class=\"booknetic_td booknetic_empty_day\"></div>";
					}

					var calendarBody = "<div class=\"booknetic_calendar\">";

					calendarBody += "<div class=\"booknetic_calendar_rows booknetic_week_names\">";

					for( var w = 0; w < booknetic.localization.day_of_week.length; w++ )
					{
						if( w > week_end_n || w < week_start_n )
							continue;

						calendarBody += "<div class=\"booknetic_td\">" + booknetic.localization.day_of_week[ w ] + "</div>";
					}

					calendarBody += "</div>";

					calendarBody += "<div class=\"booknetic_calendar_rows\">";
					calendarBody += htmlContent;
					calendarBody += "</div></div>";

					booking_panel_js.find("#booknetic_calendar_area").html( calendarBody );

					booking_panel_js.find("#booknetic_calendar_area .days[data-count]:first").trigger('click');

					booking_panel_js.find(".booknetic_month_name").text( monthNames[ _month ] + ' ' + _year );
					booking_panel_js.find('.booknetic_times_list').empty();
					booking_panel_js.find('.booknetic_times_title').text(__('Select date'));

					if( !loader )
					{
						booking_panel_js.find(".booknetic_preloader_card3_box").hide();

						booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="date_time"]').fadeIn(200, function()
						{
							booking_panel_js.find(".booknetic_appointment_container_body").scrollTop(0);
							booknetic.niceScroll();
						});
					}
				},

				drawLoadLine: function( date )
				{
					let zoom = function (input, outputSize) {
						if (input.length === outputSize) {
							return input;
						}
						const ratio = outputSize / input.length;

						const output = new Array(outputSize);

						for (let i = 0; i < outputSize; i++) {
							let value = false;

							const from = i / ratio;
							const inc = Math.max(1, 1 / ratio);

							for (let j = Math.floor(from); j < Math.floor(from + inc); j++) {
								value ||= input[j];
							}

							output[i] = value;
						}

						return output;
					};

					var fills = date in booknetic.calendarDateTimes['fills'] ? booknetic.calendarDateTimes['fills'][ date ] : [0];
					var data = date in booknetic.calendarDateTimes['dates'] ? booknetic.calendarDateTimes['dates'][ date ] : [];
					if (data.length === 1 && booknetic.dateBasedService && !( 'hide_available_slots' in booknetic.calendarDateTimes && booknetic.calendarDateTimes['hide_available_slots'] === 'on' )) {
						fills = [];
						for (let i = 0; i < data[0].max_capacity; i++) {
							fills.push(data[0].max_capacity - data[0].number_of_customers - i > 0 ? 1 : 0);
						}
					}

					var day_schedule = zoom(fills, 17);

					var line = '';
					for( var j = 0; j < day_schedule.length; j++ )
					{
						var isFree = day_schedule[j];
						line += '<i '+ (isFree?'a':'b') + '></i>';
					}

					return line;
				},

				timeToMin: function(str)
				{
					str = str.split(':');

					return parseInt(str[0]) * 60 + parseInt(str[1]);
				},

				timeZoneOffset: function()
				{
					if( BookneticData.client_time_zone == 'off' )
						return  '-';

					if ( window.Intl && typeof window.Intl === 'object' )
					{
						return Intl.DateTimeFormat().resolvedOptions().timeZone;
					}
					else
					{
						return new Date().getTimezoneOffset();
					}
				},

				datePickerFormat: function()
				{
					if( BookneticData.date_format == 'd-m-Y' )
					{
						return 'dd-mm-yyyy';
					}
					else if( BookneticData.date_format == 'm/d/Y' )
					{
						return 'mm/dd/yyyy';
					}
					else if( BookneticData.date_format == 'd/m/Y' )
					{
						return 'dd/mm/yyyy';
					}
					else if( BookneticData.date_format == 'd.m.Y' )
					{
						return 'dd.mm.yyyy';
					}

					return 'yyyy-mm-dd';
				},

				convertDate: function( date, from, to )
				{
					if( date == '' )
						return date;
					if( typeof to === 'undefined' )
					{
						to = booknetic.datePickerFormat();
					}

					to = to.replace('yyyy', 'Y').replace('dd', 'd').replace('mm', 'm');
					from = from.replace('yyyy', 'Y').replace('dd', 'd').replace('mm', 'm');

					var delimetr = from.indexOf('-') > -1 ? '-' : ( from.indexOf('.') > -1 ? '.' : '/' );
					var delimetr_to = to.indexOf('-') > -1 ? '-' : ( to.indexOf('.') > -1 ? '.' : '/' );
					var date_split = date.split(delimetr);
					var date_from_split = from.split(delimetr);
					var date_to_split = to.split(delimetr_to);

					var parts = {'m':0, 'd':0, 'Y':0};

					date_from_split.forEach(function( val, i )
					{
						parts[ val ] = i;
					});

					var new_date = '';
					date_to_split.forEach(function( val, j )
					{
						new_date += (new_date == '' ? '' : delimetr_to) + date_split[ parts[ val ] ];
					});

					return new_date;
				},

				getSelected: {

					location: function()
					{
						if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="location"]').hasClass('booknetic_menu_hidden') )
						{
							var val = booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="location"]').data('value');
						}
						else
						{
							var val = booking_panel_js.find(".booknetic_appointment_container_body > [data-step-id=\"location\"] > .booknetic_card_selected").data('id');
						}

						return val ? val : '';
					},

					staff: function()
					{
						if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="staff"]').hasClass('booknetic_menu_hidden') )
						{
							var val = booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="staff"]').data('value');
						}
						else
						{
							var val = booking_panel_js.find(".booknetic_appointment_container_body > [data-step-id=\"staff\"] > .booknetic_card_selected").data('id');
						}

						return val ? val : '';
					},

					service: function()
					{
						if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').hasClass('booknetic_menu_hidden') )
						{
							var val = booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').data('value');
						}
						else
						{
							var val = booking_panel_js.find(".booknetic_appointment_container_body > [data-step-id=\"service\"]  .booknetic_service_card_selected").data('id');
						}

						return val ? val : '';
					},

					serviceCategory: function()
					{
						return booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').data('service-category');
					},

					serviceIsRecurring: function()
					{
						let val;

						if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').hasClass('booknetic_menu_hidden') )
						{
							val = booking_panel_js.find( '.booknetic_appointment_step_element[data-step-id="service"]' ).data( 'is-recurring' );
						}
						else
						{
							val = booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="service"] .booknetic_service_card_selected').data('is-recurring');
						}

						return val == '1';
					},

					serviceExtras: function()
					{
						var extras = [];

						booking_panel_js.find(".booknetic_appointment_container_body > [data-step-id=\"service_extras\"]  .booknetic_service_extra_card_selected").each(function()
						{
							var extra_id	 = $(this).data('id'),
								quantity	 = parseInt( $(this).find('.booknetic_service_extra_quantity_input').val() ),
								max_quantity = parseInt( $( this ).find( '.booknetic_service_extra_quantity_input' ).data( 'max-quantity' ) );

							if( quantity > 0  )
							{
								extras.push({
									customer: 0,
									extra: extra_id,
									quantity: quantity,
									max_quantity: max_quantity
								});
							}
						});

						return extras;
					},

					date: function()
					{
						if( booknetic.getSelected.serviceIsRecurring() )
							return '';

						var val = booking_panel_js.find(".booknetic_selected_time").data('date-original');
						return val ? val : '';
					},

					date_in_customer_timezone: function()
					{
						if( booknetic.getSelected.serviceIsRecurring() )
							return '';

						var val = booking_panel_js.find(".booknetic_calendar_selected_day").data('date');
						return val ? val : '';
					},

					time: function()
					{
						if( booknetic.getSelected.serviceIsRecurring() )
							return booknetic.getSelected.recurringTime();

						var val = booking_panel_js.find(".booknetic_selected_time").data('time');
						return val ? val : '';
					},

					brought_people_count: function()
					{
						if( booking_panel_js.find('#booknetic_bring_someone_checkbox ').is(':checked') )
						{
							var val = Number( booking_panel_js.find('.booknetic_number_of_brought_customers_quantity_input').val() );

							return Number.isInteger( val ) ? val : 0;
						}



						return 0;
					},

					dateTime: function()
					{
						if( booknetic.getSelected.serviceIsRecurring() )
							return booknetic.getSelected.recurringTime();

						var val = booking_panel_js.find(".booknetic_selected_time").data('full-date-time-start');
						return val ? val : '';
					},

					formData: function ()
					{
						var data = { data: {} };

						var form = booking_panel_js.find(".booknetic_appointment_container_body > [data-step-id=\"information\"]");

						form.find('input[name]#bkntc_input_name, input[name]#bkntc_input_surname, input[name]#bkntc_input_email, input[name]#bkntc_input_phone ').each(function()
						{
							var name	= $(this).attr('name'),
								value	= name == 'phone' && typeof intlTelInputUtils != 'undefined' ? $(this).data('iti').getNumber(intlTelInputUtils.numberFormat.E164) : $(this).val();

							data['data'][name] = value;
						});

						return data;
					},

					paymentMethod: function ()
					{
						if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="confirm_details"]').hasClass('booknetic_menu_hidden') )
							return 'local';

						return booking_panel_js.find('.booknetic_payment_method.booknetic_payment_method_selected').data('payment-type');
					},

					paymentDepositFullAmount: function ()
					{
						return booking_panel_js.find('input[name="input_deposit"][type="radio"]:checked').val() == '0' ? true : false;
					},

					recurringStartDate: function()
					{
						var val = booking_panel_js.find("#booknetic_recurring_start").val();

						if( val == '' || val == undefined )
							return '';

						return booknetic.convertDate( val, booknetic.datePickerFormat(), 'Y-m-d' );
					},

					recurringEndDate: function()
					{
						var val = booking_panel_js.find("#booknetic_recurring_end").val();

						if( val == '' || val == undefined )
							return '';

						return booknetic.convertDate( val, booknetic.datePickerFormat(), 'Y-m-d' );
					},

					recurringTimesArr: function()
					{
						if( !booknetic.serviceData )
							return JSON.stringify( {} );

						var repeatType		=	booknetic.serviceData['repeat_type'],
							recurringTimes	=	{};

						if( repeatType == 'weekly' )
						{
							booking_panel_js.find(".booknetic_times_days_of_week_area > .booknetic_active_day").each(function()
							{
								var dayNum = $(this).data('day');
								var time = $(this).find('.booknetic_wd_input_time').val();

								recurringTimes[ dayNum ] = time;
							});

							recurringTimes = JSON.stringify( recurringTimes );
						}
						else if( repeatType == 'daily' )
						{
							recurringTimes = booking_panel_js.find("#booknetic_daily_recurring_frequency").val();
						}
						else if( repeatType == 'monthly' )
						{
							recurringTimes = booking_panel_js.find("#booknetic_monthly_recurring_type").val();
							if( recurringTimes == 'specific_day' )
							{
								recurringTimes += ':' + ( booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").val() == null ? '' : booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").val().join(',') );
							}
							else
							{
								recurringTimes += ':' + booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").val();
							}
						}

						return recurringTimes;
					},

					recurringTimesArrFinish: function()
					{
						var recurringDates = [];
						var hasTimeError = false;

						booking_panel_js.find("#booknetic_recurring_dates > tr").each(function()
						{
							var sDate = $(this).find('[data-date]').data('date');
							var sTime = $(this).find('[data-time]').data('time');
							// if tried to change the time
							if( $(this).find('.booknetic_time_select').length )
							{
								sTime = $(this).find('.booknetic_time_select').val();
								if( sTime == '' )
								{
									hasTimeError = true;
								}
							}
							else if( $(this).find('.booknetic_data_has_error').length > 0 )
							{
								hasTimeError = true;
							}

							recurringDates.push([ sDate, sTime ]);
						});

						if( hasTimeError )
						{
							return false;
						}

						return JSON.stringify( recurringDates );
					},

					recurringTime: function ()
					{
						if( !booknetic.serviceData )
							return  '';

						var repeatType	=	booknetic.serviceData['repeat_type'],
							time		=	'';

						if( repeatType == 'daily' )
						{
							time = booking_panel_js.find("#booknetic_daily_time").val();
						}
						else if( repeatType == 'monthly' )
						{
							time = booking_panel_js.find("#booknetic_monthly_time").val();
						}

						return time;
					}

				},

				ajaxParameters: function ( defaultData )
				{
					var data = new FormData();

					data.append( 'id', booknetic.appointmentId );
					data.append( 'location', booknetic.getSelected.location() );
					data.append( 'staff', booknetic.getSelected.staff() );
					data.append( 'service_category', booknetic.getSelected.serviceCategory() );
					data.append( 'service', booknetic.getSelected.service() );
					data.append( 'service_extras', JSON.stringify( booknetic.getSelected.serviceExtras() ) );


					data.append( 'date', booknetic.getSelected.date() );
					data.append( 'time', booknetic.getSelected.time() );
					data.append( 'brought_people_count', booknetic.getSelected.brought_people_count() );

					data.append( 'recurring_start_date', booknetic.getSelected.recurringStartDate() );
					data.append( 'recurring_end_date', booknetic.getSelected.recurringEndDate() );
					data.append( 'recurring_times', booknetic.getSelected.recurringTimesArr() );
					data.append( 'appointments', booknetic.getSelected.recurringTimesArrFinish() );

					var customerData = booknetic.getSelected.formData();
					for( var n in customerData['data'] )
					{
						data.append( 'customer_data['+n+']', customerData['data'][n] );
					}

					data.append( 'payment_method', booknetic.getSelected.paymentMethod() );
					data.append( 'deposit_full_amount', booknetic.getSelected.paymentDepositFullAmount() ? 1 : 0 );
					data.append( 'client_time_zone', booknetic.timeZoneOffset() );

					data.append( 'google_recaptcha_token', google_recaptcha_token );
					data.append( 'google_recaptcha_action', google_recaptcha_action );

					if( typeof defaultData != 'undefined' )
					{
						for ( var key in defaultData )
						{
							data.append( key, defaultData[key] );
						}
					}

					return bookneticHooks.doFilter( 'appointment_ajax_data', data, booknetic );
				},

				calcRecurringTimes: function()
				{
					booknetic.serviceFixPeriodEndDate();

					var fullPeriod			=	booknetic.serviceData['full_period_value'];
					var isFullPeriodFixed	=	fullPeriod > 0 ;
					var repeatType			=	booknetic.serviceData['repeat_type'];
					var startDate			=	booknetic.getSelected.recurringStartDate();
					var endDate				=	booknetic.getSelected.recurringEndDate();

					if( startDate == '' || endDate == '' )
						return;

					startDate	= new Date( startDate );
					endDate		= new Date( endDate );

					var cursor	= startDate,
						numberOfAppointments = 0,
						frequency = (repeatType == 'daily') ? booking_panel_js.find('#booknetic_daily_recurring_frequency').val() : 1;

					if( !( frequency >= 1 ) )
					{
						frequency = 1;
						if( repeatType == 'daily' )
						{
							booking_panel_js.find('#booknetic_daily_recurring_frequency').val('1');
						}
					}

					var activeDays = {};
					if( repeatType == 'weekly' )
					{
						booking_panel_js.find(".booknetic_times_days_of_week_area > .booknetic_active_day").each(function()
						{
							activeDays[ $(this).data('day') ] = true;
						});

						if( $.isEmptyObject( activeDays ) )
						{
							return;
						}
					}
					else if( repeatType == 'monthly' )
					{
						var monthlyRecurringType = booking_panel_js.find("#booknetic_monthly_recurring_type").val();
						var monthlyDayOfWeek = booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").val();

						var selectedDays = booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").select2('val');

						if( selectedDays )
						{
							for( var i = 0; i < selectedDays.length; i++ )
							{
								activeDays[ selectedDays[i] ] = true;
							}
						}
					}

					while( cursor <= endDate )
					{
						var weekNum = cursor.getDay();
						var dayNumber = parseInt( cursor.getDate() );
						weekNum = weekNum > 0 ? weekNum : 7;
						var dateFormat = cursor.getFullYear() + '-' + booknetic.zeroPad( cursor.getMonth() + 1 ) + '-' + booknetic.zeroPad( cursor.getDate() );

						if( repeatType == 'monthly' )
						{
							if( ( monthlyRecurringType == 'specific_day' && typeof activeDays[ dayNumber ] != 'undefined' ) || booknetic.getMonthWeekInfo(cursor, monthlyRecurringType, monthlyDayOfWeek) )
							{
								if(
									// if is not off day for staff or service
									!( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
									// if is not holiday for staff or service
									typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
								)
								{
									numberOfAppointments++;
								}
							}
						}
						else if(
							// if weekly repeat type then only selected days of week...
							( typeof activeDays[ weekNum ] != 'undefined' || repeatType == 'daily' ) &&
							// if is not off day for staff or service
							!( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
							// if is not holiday for staff or service
							typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
						)
						{
							numberOfAppointments++;
						}

						cursor = new Date( cursor.getTime() + 1000 * 24 * 3600 * frequency );
					}

					booking_panel_js.find('#booknetic_recurring_times').val( numberOfAppointments );

				},

				initRecurringElements: function( )
				{
					booknetic.select2Ajax( booking_panel_js.find(".booknetic_wd_input_time, #booknetic_daily_time, #booknetic_monthly_time"), 'get_available_times_all', function( select )
					{
						var dayNumber = ( select.attr('id') == 'booknetic_daily_time' || select.attr('id') == 'booknetic_monthly_time' ) ? -1 : select.attr('id').replace('booknetic_time_wd_', '');

						return booknetic.formDataToObject( booknetic.ajaxParameters({day_number: dayNumber}) );
					});

					booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").select2({
						theme: 'bootstrap',
						placeholder: __('select'),
						allowClear: true
					});
					booking_panel_js.find("#booknetic_monthly_recurring_type, #booknetic_monthly_recurring_day_of_week").select2({
						theme: 'bootstrap',
						placeholder: __('select'),
						minimumResultsForSearch: -1
					});

					booking_panel_js.find('#booknetic_monthly_recurring_type').trigger('change');

					booknetic.initDatepicker( booking_panel_js.find("#booknetic_recurring_start") );
					booknetic.initDatepicker( booking_panel_js.find("#booknetic_recurring_end") );

					booknetic.serviceFixPeriodEndDate();
					booknetic.serviceFixFrequency();
					booking_panel_js.find("#booknetic_recurring_start").trigger('change');
				},

				serviceFixPeriodEndDate: function()
				{
					var serviceData = booknetic.serviceData;

					if( serviceData && serviceData['full_period_value'] > 0 )
					{
						booking_panel_js.find("#booknetic_recurring_end").attr('disabled', true);
						booking_panel_js.find("#booknetic_recurring_times").attr('disabled', true);

						var startDate = booknetic.getSelected.recurringStartDate();

						if( serviceData['full_period_type'] == 'month' )
						{
							endDate = new Date( startDate + "T00:00:00" );
							endDate.setMonth( endDate.getMonth() + parseInt( serviceData['full_period_value'] ) );
							endDate.setDate( endDate.getDate() - 1 );

							booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
						}
						else if( serviceData['full_period_type'] == 'week' )
						{

							endDate = new Date( startDate + "T00:00:00" );
							endDate.setDate( endDate.getDate() + parseInt( serviceData['full_period_value'] ) * 7 - 1 );

							booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
						}
						else if( serviceData['full_period_type'] == 'day' )
						{
							endDate = new Date( startDate + "T00:00:00" );
							endDate.setDate( endDate.getDate() + parseInt( serviceData['full_period_value'] ) - 1 );

							booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
						}
						else if( serviceData['full_period_type'] == 'time' )
						{
							if( booknetic.getSelected.recurringEndDate() == '' )
							{
								var startDate = new Date( booknetic.getSelected.recurringStartDate() );
								var endDate = new Date( startDate.setMonth( startDate.getMonth() + 1 ) );

								booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
							}

							booking_panel_js.find("#booknetic_recurring_times").val( serviceData['full_period_value'] ).trigger('keyup');
						}
					}
					else
					{
						booking_panel_js.find("#booknetic_recurring_end").attr('disabled', false);
						booking_panel_js.find("#booknetic_recurring_times").attr('disabled', false);

						if( booknetic.getSelected.recurringEndDate() == '' )
						{
							var startDate = new Date( booknetic.getSelected.recurringStartDate() );
							var endDate = new Date( startDate.setMonth( startDate.getMonth() + 1 ) );

							booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
						}
					}
				},

				serviceFixFrequency: function()
				{
					var serviceData = booknetic.serviceData;

					if( serviceData && serviceData['repeat_frequency'] > 0 && serviceData['repeat_type'] == 'daily' )
					{
						booking_panel_js.find("#booknetic_daily_recurring_frequency").val( serviceData['repeat_frequency'] ).attr('disabled', true);
					}
					else
					{
						booking_panel_js.find("#booknetic_daily_recurring_frequency").attr('disabled', false);
					}
				},

				getMonthWeekInfo: function( date, type, dayOfWeek )
				{
					var jsDate = new Date( date ),
						weekd = jsDate.getDay();
					weekd = weekd == 0 ? 7 : weekd;

					if( weekd != dayOfWeek )
					{
						return false;
					}

					var month = jsDate.getMonth()+1,
						year = jsDate.getFullYear();

					if( type == 'last' )
					{
						var nextWeek = new Date(jsDate.getTime());
						nextWeek.setDate( nextWeek.getDate() + 7 );

						return nextWeek.getMonth()+1 != month ? true : false;
					}

					var firstDayOfMonth = new Date( year + '-' + booknetic.zeroPad( month ) + '-01' ),
						firstWeekDay = firstDayOfMonth.getDay();
					firstWeekDay = firstWeekDay == 0 ? 7 : firstWeekDay;

					var dif = ( dayOfWeek >= firstWeekDay ? dayOfWeek : parseInt(dayOfWeek)+7 ) - firstWeekDay;

					var days = jsDate.getDate() - dif,
						dNumber = parseInt(days / 7)+1;

					return type == dNumber ? true : false;
				},

				confirmAppointment: function ()
				{
					booknetic.ajax( 'confirm', booknetic.ajaxParameters(), function ( result )
					{
						booknetic.refreshGoogleReCaptchaToken();

						booknetic.appointmentId = result['id'];
						booknetic.uniqueToken   = result['unique_token'];

						if( booknetic.getSelected.paymentMethod() === 'local' )

						{
							booknetic.paymentFinished( true );
							booknetic.showFinishStep();
						}

						booking_panel_js.find('#booknetic_add_to_google_calendar_btn').data('url', result['google_calendar_url'] );
					} , true, function( result )
					{
						if( typeof result['id'] != 'undefined' )
						{
							booknetic.appointmentId = result['id'];
							booknetic.uniqueToken   = typeof result['uniqueToken'] != 'undefined' ? result['uniqueToken'] : null;
						}
					});
				},

				waitPaymentFinish: function()
				{
					if( booknetic.paymentWindow.closed )
					{
						booknetic.loading(0);

						booknetic.showFinishStep();

						return;
					}

					setTimeout( booknetic.waitPaymentFinish, 1000 );
				},

				paymentFinished: function ( status )
				{
					booknetic.paymentStatus = status;
					booking_panel_js.find(".booknetic_appointment_finished_code").text( booknetic.zeroPad( booknetic.appointmentId, 4 ) );

					if( booknetic.paymentWindow && !booknetic.paymentWindow.closed )
					{
						booknetic.paymentWindow.close();
					}
				},

				showFinishStep: function ()
				{
					if( booknetic.paymentStatus === true )
					{
						booking_panel_js.find('.booknetic_appointment_container').fadeOut(95);
						booking_panel_js.find('.booknetic_appointment_steps').fadeOut(100, function ()
						{
							booking_panel_js.find('.booknetic_appointment_finished').fadeIn(100).css('display', 'flex');
						});
					}
					else
					{
						booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="confirm_details"]').fadeOut( 150, function()
						{
							booking_panel_js.find('.booknetic_appointment_container_body > .booknetic_appointment_finished_with_error').removeClass('booknetic_hidden').hide().fadeIn( 150 );
						});

						booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').fadeOut( 150, function()
						{
							booking_panel_js.find('.booknetic_try_again_btn').removeClass('booknetic_hidden').hide().fadeIn( 150 );
						});
						booking_panel_js.find('.booknetic_prev_step').css('opacity', '0').attr('disabled', true);
					}
				},

				fadeInAnimate: function(el, sec, delay)
				{
					sec = sec > 0 ? sec : 150;
					delay = delay > 0 ? delay : 50;

					$(el).hide().each(function (i)
					{
						(function( i, t )
						{
							setTimeout( function ()
							{
								t.fadeIn( (i > 6 ? 6 : i) * sec );
							}, (i > 6 ? 6 : i) * delay );
						})( i, $(this) );
					});
				},

				fadeOutAnimate: function(el, sec, delay)
				{
					sec = sec > 0 ? sec : 150;
					delay = delay > 0 ? delay : 50;

					$(el).each(function (i)
					{
						(function( i, t )
						{
							setTimeout( function ()
							{
								t.fadeOut( (i > 6 ? 6 : i) * sec );
							}, (i > 6 ? 6 : i) * delay );
						})( i, $(this) );
					});
				},


				_niceScrol: false,
				niceScroll: function ()
				{
					if( !booknetic._niceScrol && !booknetic.isMobileView() )
					{
						booking_panel_js.find(".booknetic_appointment_container_body").niceScroll({
							cursorcolor: "#e4ebf4",
							bouncescroll: true,
							preservenativescrolling: false
						});

						booknetic._niceScrol = true;

						return;
					}

					if( booknetic.isMobileView() && booknetic._niceScrol )
					{
						booknetic._niceScrol = false;

						booking_panel_js.find(".booknetic_appointment_container_body").getNiceScroll().remove();

						if ( $( '#country-listbox' ).length )
						{
							$( '#country-listbox' ).getNiceScroll().remove();
						}

						return;
					}

					if( booknetic._niceScrol )
					{
						booking_panel_js.find(".booknetic_appointment_container_body").getNiceScroll().resize();

						if ( $( '#country-listbox' ).length )
						{
							$( '#country-listbox' ).getNiceScroll().resize();
						}
					}
				},

				initDatepicker: function ( el )
				{
					bookneticdatepicker( el[0], {
						formatter: function (input, date, instance)
						{
							var val = date.getFullYear() + '-' + booknetic.zeroPad( date.getMonth() + 1 ) + '-' + booknetic.zeroPad( date.getDate() );
							input.value = booknetic.convertDate( val, 'Y-m-d' );
						},
						startDay: BookneticData.week_starts_on == 'sunday' ? 0 : 1,
						customDays: [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
						customMonths: [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
						onSelect: function( input )
						{
							$(input.el).trigger('change');
						}
					});
				},

				refreshGoogleReCaptchaToken: function ()
				{
					if( 'google_recaptcha_site_key' in BookneticData )
					{
						grecaptcha.execute( BookneticData['google_recaptcha_site_key'], { action: google_recaptcha_action }).then(function (token)
						{
							google_recaptcha_token = token;
						});
					}
				},

				isMobileView: function ()
				{
					return window.matchMedia('(max-width: 1000px)').matches;
				},

				stepManager: {

					stepValidation: function ( step )
					{
						let status = true;
						let errorMsg = '';

						if( step == 'location' )
						{
							if( !( booknetic.getSelected.location() > 0 ) )
							{
								status      = false;
								errorMsg    = __('select_location');
							}
						}
						else if( step == 'staff' )
						{
							if( !( booknetic.getSelected.staff() > 0 || booknetic.getSelected.staff() == -1 ) )
							{
								status      = false;
								errorMsg    = __('select_staff');
							}
						}
						else if( step == 'service' )
						{
							if( !( booknetic.getSelected.service() > 0 ) )
							{
								status      = false;
								errorMsg    = __('select_service');
							}
						}
						else if ( step == 'service_extras' )
						{
							booknetic.getSelected.serviceExtras().forEach( function ( extra ) {
								if ( extra.quantity > extra.max_quantity )
								{
									status   = false;
									errorMsg = __( 'You have exceed the maximum value for extra service(s).' );
								}
							} );
						}
						else if( step == 'date_time' )
						{
							if( booknetic.getSelected.serviceIsRecurring() )
							{
								var service_repeat_type = booknetic.serviceData['repeat_type'];

								if( service_repeat_type == 'weekly' )
								{
									if( booking_panel_js.find('.booknetic_times_days_of_week_area > .booknetic_active_day').length == 0 )
									{
										status      = false;
										errorMsg    = __('select_week_days');
									}
									else
									{
										var timeNotSelected = false;
										booking_panel_js.find('.booknetic_times_days_of_week_area > .booknetic_active_day').each(function ()
										{
											if( $(this).find('.booknetic_wd_input_time').val() == null )
											{
												timeNotSelected = true;
												return;
											}
										});

										if( timeNotSelected )
										{
											status      = false;
											errorMsg    = __('date_time_is_wrong');
										}
									}
								}
								else if( service_repeat_type == 'monthly' )
								{

								}

								if( booknetic.getSelected.recurringStartDate() == '' )
								{
									status      = false;
									errorMsg    = __('select_start_date');
								}

								if( booknetic.getSelected.recurringEndDate() == '' )
								{
									status      = false;
									errorMsg    = __('select_end_date');
								}

							}
							else
							{
								if( booknetic.getSelected.date_in_customer_timezone() == '')
								{
									status      = false;
									errorMsg    = __('select_date');
								}

								if( booknetic.getSelected.time() == '')
								{
									status      = false;
									errorMsg    = __('select_time');
								}
							}

						}
						else if( step == 'recurring_info' )
						{
							if( booknetic.getSelected.recurringTimesArrFinish() === false )
							{
								status      = false;
								errorMsg    = __('select_available_time');
							}
						}
						else if( step == 'information' )
						{
							var hasError = false;

							booking_panel_js.find( 'label[for="bkntc_input_name"], label[for="bkntc_input_surname"], label[for="bkntc_input_email"], label[for="bkntc_input_phone"]' ).each( function ()
							{
								var el = $( this ).next();
								var required = $( this ).is( '[data-required="true"]' );

								if ( el.is( '.booknetic_number_of_brought_customers_quantity' ) )
								{
									el = el.find( 'input' );
									if ( el.data( 'max-quantity' ) < el.val() )
									{
										if( $("#booknetic_bring_someone_checkbox").is(":checked"))
										{
											el.addClass( 'booknetic_input_error' );
											hasError =  __( 'You have exceed the maximum value for number of people' );
										}
									}
								}

								if( el.is('div.iti') )
								{
									el = el.find('input');
								}

								if( el.is('#bkntc_input_name , #bkntc_input_surname , #bkntc_input_email, #bkntc_input_phone') )
								{
									var value = el.val();

									if( required && (value.trim() == '' || value == null) )
									{
										if( el.is('select') )
										{
											el.next().find('.select2-selection').addClass('booknetic_input_error');
										}
										else if( el.is('input[type="file"]') )
										{
											el.next().addClass('booknetic_input_error');
										}
										else
										{
											el.addClass('booknetic_input_error');
										}
										hasError = __('fill_all_required');
									}
									else if( el.attr('name') === 'email' )
									{
										var email_regexp = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
										var checkEmail = email_regexp.test(String(value).toLowerCase());

										if( !( (value == '' && !required) || checkEmail ) )
										{
											el.addClass('booknetic_input_error');
											hasError = __('email_is_not_valid');
										}
									}
									else if( el.attr('name') === 'phone' )
									{
										if( !( value == '' && !required || String(value).match(/^\+?[0-9\(\) \-]+$/) ) )
										{
											el.addClass('booknetic_input_error');
											hasError = __('phone_is_not_valid');
										}
									}
								}

							});

							if( hasError )
							{
								status      = false;
								errorMsg    = hasError;
							}
						}

						let result = {
							status: status,
							errorMsg: errorMsg
						};

						return bookneticHooks.doFilter( 'step_validation_' + step, result, booknetic );
					},

					loadStep: function( step )
					{
						var current_step_el	= booking_panel_js.find('.booknetic_appointment_step_element.booknetic_active_step');
						var next_step_el	= booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="'+step+'"]');

						while( next_step_el.hasClass('booknetic_menu_hidden') )
							next_step_el = next_step_el.next();

						booking_panel_js.find(".booknetic_next_step, .booknetic_prev_step").attr('disabled', true);

						var step_id		= next_step_el.data('step-id');
						var loader		= booking_panel_js.find('.booknetic_preloader_' + next_step_el.data('loader') + '_box');

						if( current_step_el.length > 0 )
						{
							current_step_el.removeClass('booknetic_active_step');
							var current_step_id	= current_step_el.data('step-id');
						}
						next_step_el.addClass('booknetic_active_step');
						booking_panel_js.find(".booknetic_appointment_container_header").text( next_step_el.data('title') );

						var next2_step_el	= next_step_el.next('.booknetic_appointment_step_element');

						while( next2_step_el.hasClass('booknetic_menu_hidden') )
							next2_step_el = next2_step_el.next();

						if ( next2_step_el.length == 0 ) {
							booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').show();
							booking_panel_js.find('.booknetic_next_step.booknetic_next_step_btn').hide();
						}
						else
						{
							booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').hide();
							booking_panel_js.find('.booknetic_next_step.booknetic_next_step_btn').show();
						}

						var loadNewStep = function()
						{
							if( ! booknetic.stepManager.needToReload(step_id) )
							{
								booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"]').show();

								booknetic.fadeInAnimate('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"] .booknetic_fade');

								setTimeout(function ()
								{
									booking_panel_js.find(".booknetic_appointment_container_body").scrollTop(0);
									booknetic.niceScroll();
									booking_panel_js.find(".booknetic_next_step, .booknetic_prev_step").attr('disabled', false);
								}, 101 + booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"] .booknetic_fade').length * 50);
							}
							else
							{
								loader.removeClass('booknetic_hidden').hide().fadeIn(200);

								booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"]').empty();

								booknetic.ajax( 'get_data_' + step_id, booknetic.ajaxParameters(), function ( result )
								{
									if ( step_id === 'service_extras' && BookneticData[ 'skip_extras_step_if_need' ] === 'on' && result.html.indexOf( 'booknetic_empty_box' ) > -1 )
									{
										loader.hide();
										booking_panel_js.find( '.booknetic_appointment_step_element[data-step-id="service_extras"]:not(.booknetic_menu_hidden)' ).hide();
										booknetic.stepManager.refreshStepNumbers();
										booknetic.stepManager.goForward();

										return;
									}

									loader.fadeOut(200, function ()
									{
										booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"]').show().html( booknetic.htmlspecialchars_decode( result['html'] ) );

										booknetic.fadeInAnimate('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"] .booknetic_fade');

										booking_panel_js.find(".booknetic_next_step, .booknetic_prev_step").attr('disabled', false);

										setTimeout(function ()
										{
											booking_panel_js.find(".booknetic_appointment_container_body").scrollTop(0);
											booknetic.niceScroll();
										}, 101 + booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"] .booknetic_fade').length * 50);


										bookneticHooks.doAction( 'steps_' + step_id, booknetic );

										if( step_id == 'information' )
										{
											var phone_input = booking_panel_js.find('#bkntc_input_phone');

											phone_input.data('iti', window.intlTelInput( phone_input[0], {
												utilsScript: BookneticData.assets_url + "js/utilsIntlTelInput.js",
												initialCountry: phone_input.data('country-code')
											}));

											if ( ! booknetic.isMobileView() )
											{
												$( '#country-listbox' ).niceScroll( {
													cursorcolor: "#e4ebf4",
													bouncescroll: true,
													preservenativescrolling: false
												} );
											}
										}
										else if( step_id == 'date_time' )
										{
											booknetic.serviceData = null;
											booknetic.dateBasedService   = result['service_info']['date_based'];
											booknetic.serviceMaxCapacity = result['service_info']['max_capacity'];

											if( result['service_type'] == 'non_recurring' )
											{
												booknetic.calendarDateTimes = result['data'];
												booknetic.time_show_format = result['time_show_format'];
												booknetic.nonRecurringCalendar(undefined, undefined, false, false);

												// if current month is empty, then next month will be loaded
												// current_month_is_empty is on-fly property, is not initialized on main object, and it is always undefined by default
												if ( typeof booknetic.current_month_is_empty === 'undefined' )
												{
													booknetic.current_month_is_empty = true;
													for ( let i in booknetic.calendarDateTimes.dates )
													{
														if ( booknetic.calendarDateTimes.dates.hasOwnProperty( i ) && booknetic.calendarDateTimes.dates[ i ].length > 0 ) {
															booknetic.current_month_is_empty = false;

															break;
														}
													}

													if ( booknetic.current_month_is_empty === true )
													{
														let now     = new Date();
														let year    = now.getFullYear();
														let month   = now.getMonth();

														if( month < 11 )
														{
															month++;
														}
														else
														{
															year++;
															month = 0;
														}

														booknetic.nonRecurringCalendar( year, month );
													}
												}

												if ( ! booknetic.isMobileView() )
												{
													booking_panel_js.find( '.booknetic_times_list' ).niceScroll( {
														cursorcolor: '#e4ebf4',
														bouncescroll: true
													} );
												}
											}
											else
											{
												booknetic.serviceData = result['service_info'];
												booknetic.initRecurringElements();
											}
										}

										if( booknetic.getSelected.serviceIsRecurring() )
										{
											booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="recurring_info"].booknetic_menu_hidden').slideDown(300, function ()
											{
												$(this).removeClass('booknetic_menu_hidden');
												booknetic.stepManager.refreshStepNumbers();
											});
										}
										else
										{
											booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="recurring_info"]:not(.booknetic_menu_hidden)').slideUp(300, function ()
											{
												$(this).addClass('booknetic_menu_hidden');
												booknetic.stepManager.refreshStepNumbers();
											});
										}

										if( step_id == 'confirm_details' )
										{
											if ( ! booknetic.isMobileView() )
											{
												$( '.booknetic_portlet_content' ).niceScroll( {
													cursorcolor: "#e4ebf4",
													bouncescroll: true,
													preservenativescrolling: false
												} );
											}
										}
									});

								}, false , function ()
								{
									loader.fadeOut(200, function ()
									{
										booking_panel_js.find(".booknetic_next_step, .booknetic_prev_step").attr('disabled', false);
										booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step").removeClass('booknetic_active_step').prev().addClass('booknetic_active_step').removeClass('booknetic_selected_step');

										if( current_step_el.length > 0 )
										{
											booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + current_step_id + '"]').fadeIn(100);
										}
										else
										{
											setTimeout(function ()
											{
												booknetic.stepManager.loadStep(step);
											}, 3000);
										}
									});
								} );
							}
						}

						if( current_step_el.length > 0 )
						{
							booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + current_step_id + '"]').fadeOut( 200, loadNewStep);
						}
						else
						{
							loadNewStep();
						}
					},

					goForward: function ()
					{
						let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
							current_step_id = current_step_el.data('step-id'),
							next_step_el	= booknetic.stepManager.getNextStep(),
							next_step_id    = next_step_el.data('step-id'),
							validate_step   = booknetic.stepManager.stepValidation( current_step_id );

						if( validate_step.status == false )
						{
							booknetic.toast( validate_step.errorMsg );
							return;
						}

						if( next_step_el.length == 0 )
						{
							booknetic.confirmAppointment();
							return;
						}

						if( booknetic.save_step_data != null && JSON.stringify( [ ...booknetic.save_step_data.entries() ] ) != JSON.stringify( [ ...booknetic.ajaxParameters().entries() ] ) )
						{
							let startToEmpty = next_step_el;
							while( startToEmpty.length > 0 )
							{
								booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="'+startToEmpty.data('step-id')+'"]').empty();
								startToEmpty = startToEmpty.next();
							}
						}

						booknetic.toast( false );
						booknetic.stepManager.loadStep( next_step_id );
						current_step_el.addClass('booknetic_selected_step');

						if( booknetic.isMobileView() )
						{
							$('html,body').animate({scrollTop: parseInt(booking_panel_js.offset().top) - 100}, 1000);
						}
					},

					goBack: function ()
					{
						let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
							prev_step_el    = booknetic.stepManager.getPrevStep(),
							step_id         = prev_step_el.data( 'step-id' );

						current_step_el.removeClass('booknetic_selected_step').nextAll('.booknetic_appointment_step_element').removeClass('booknetic_selected_step');

						if( prev_step_el.length > 0 )
						{
							booknetic.ajax( 'get_data_' + step_id, booknetic.ajaxParameters(), function ( result )
							{
								if ( step_id === 'service_extras' && BookneticData[ 'skip_extras_step_if_need' ] === 'on' && result.html.indexOf( 'booknetic_empty_box' ) > -1 )
								{
									booknetic.stepManager.goBack();
									booking_panel_js.find( '.booknetic_appointment_step_element[data-step-id="service_extras"]:not(.booknetic_menu_hidden)' ).show();
									booknetic.stepManager.refreshStepNumbers();
								}
							}, false );
							booknetic.save_step_data = booknetic.ajaxParameters();

							current_step_el.removeClass('booknetic_active_step');
							prev_step_el.addClass('booknetic_active_step');

							booking_panel_js.find(".booknetic_next_step,.booknetic_prev_step").attr('disabled', true);
							booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + current_step_el.data('step-id') + '"]').fadeOut(200, function()
							{
								booking_panel_js.find(".booknetic_next_step,.booknetic_prev_step").attr('disabled', false);
								booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + prev_step_el.data('step-id') + '"]').fadeIn(200, function ()
								{
									booknetic.niceScroll();
								});
							});

							booking_panel_js.find(".booknetic_appointment_container_header").text( prev_step_el.data('title') );
						}

						booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').hide();
						booking_panel_js.find('.booknetic_next_step.booknetic_next_step_btn').show();
					},

					getNextStep: function ()
					{
						let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
							next_step_el	= current_step_el.next('.booknetic_appointment_step_element');

						while( next_step_el.hasClass('booknetic_menu_hidden') )
							next_step_el = next_step_el.next();

						return next_step_el;
					},

					getPrevStep: function ()
					{
						let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
							prev_step_el    = current_step_el.prev('.booknetic_appointment_step_element');

						while( prev_step_el.hasClass('booknetic_menu_hidden') )
							prev_step_el = prev_step_el.prev();

						return prev_step_el;
					},

					refreshStepNumbers: function ()
					{
						var index = 1;

						booking_panel_js.find('.booknetic_appointment_steps_body > .booknetic_appointment_step_element').each(function()
						{
							if( $(this).css('display') != 'none' )
							{
								$(this).find('.booknetic_badge').text( index );
								index++;
							}
						});
					},

					needToReload: function( step_id )
					{
						if( step_id == 'confirm_details' )
							return true;

						if( booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + step_id + '"] > *').length > 0 )
						{
							return false;
						}

						return true;
					},

				},
				validateEmail: function(email){
					const mailFormat = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
					return !!email.match(mailFormat);
				},
				validatePhone: function (phone) {
					const phoneFormat = /^([0-9\s\(\)+-]+)$/;
					return !!phone.match(phoneFormat);
				}

			};

			// steplerle bagli basic eventler
			booking_panel_js.on('click', '.booknetic_next_step', function()
			{
				booknetic.stepManager.goForward();
			}).on('click', '.booknetic_prev_step', function()
			{
				booknetic.stepManager.goBack();
			});

			booking_panel_js.on('click', '#booknetic_finish_btn', function ()
			{
				if( $(this).data('redirect-url') == '' )
				{
					location.reload();
				}
				else
				{
					location.href = $(this).data('redirect-url');
				}
			}).on('click', '#booknetic_start_new_booking_btn', function ()
			{

				booking_panel_js.find('.booknetic_appointment_finished').fadeOut(100, function()
				{
					booking_panel_js.find('.booknetic_appointment_steps').fadeIn(100);
					booking_panel_js.find('.booknetic_appointment_container').fadeIn(100);
				});

				booking_panel_js.find(".booknetic_selected_step").removeClass('booknetic_selected_step');
				booking_panel_js.find(".booknetic_active_step").removeClass('booknetic_active_step');

				booknetic.calendarDateTimes		= {};
				booknetic.time_show_format		= 1;
				booknetic.calendarYear			= null;
				booknetic.calendarMonth			= null;
				booknetic.paymentWindow			= null;
				booknetic.paymentStatus			= null;
				booknetic.appointmentId			= null;
				booknetic.uniqueToken			= null;
				booknetic.save_step_data        = null;

				var start_step = booking_panel_js.find(".booknetic_appointment_step_element:not(.booknetic_menu_hidden):eq(0)");
				start_step.addClass('booknetic_active_step');
				booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id] ').empty();
				booknetic.stepManager.loadStep( start_step.data('step-id') );

				booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id]').hide();
				booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="' + start_step.data('step-id') + '"]').show();

				booking_panel_js.find('.booknetic_card_selected').removeClass('booknetic_card_selected');
				booking_panel_js.find('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');
				booking_panel_js.find('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');

				booking_panel_js.find(".booknetic_calendar_selected_day").data('date', null);
				booking_panel_js.find(".booknetic_selected_time").data('time', null);

				booknetic.niceScroll();

			}).on('click', '#booknetic_add_to_google_calendar_btn', function ()
			{
				window.open( $(this).data('url') );
			}).on('click', '.booknetic_try_again_btn', function ()
			{
				booknetic.ajax('delete_unpaid_appointment', booknetic.ajaxParameters({ unique_token: booknetic.uniqueToken }), function ()
				{
					booknetic.appointmentId = null;
					booknetic.uniqueToken   = null;

					booking_panel_js.find('.booknetic_appointment_finished_with_error').fadeOut(150, function ()
					{
						booking_panel_js.find('.booknetic_appointment_container_body > [data-step-id="confirm_details"]').fadeIn(150, function ()
						{
							booknetic.niceScroll();
						});
					});

					booking_panel_js.find('.booknetic_try_again_btn').fadeOut(150, function ()
					{
						booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').fadeIn(150);
						booking_panel_js.find('.booknetic_prev_step').css('opacity', '1').attr('disabled', false);
					});
				});
			});

			// location & staff stepi
			booking_panel_js.on('click', '.booknetic_card', function()
			{
				$(this).parent().children('.booknetic_card_selected').removeClass('booknetic_card_selected');
				$(this).addClass('booknetic_card_selected');

				booknetic.stepManager.goForward();
			});

			// services stepi
			booking_panel_js.on('click', '.booknetic_service_card', function()
			{
				$(this).parent().children('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');
				$(this).addClass('booknetic_service_card_selected');

				booknetic.stepManager.goForward();
			});

			// Service Extras stepi
			booking_panel_js.on('click', '.booknetic_extra_on_off_mode', function (e)
			{
				if( $(e.target).is('.booknetic_service_extra_quantity_inc, .booknetic_service_extra_quantity_dec') )
					return;

				if( $(this).hasClass('booknetic_service_extra_card_selected') )
				{
					$(this).find('.booknetic_service_extra_quantity_dec').trigger('click');
				}
				else
				{
					$(this).find('.booknetic_service_extra_quantity_inc').trigger('click');
				}
			}).on('click', '.booknetic_service_extra_quantity_inc', function()
			{
				var quantity = parseInt( $(this).prev().val() );
				quantity = quantity > 0 ? quantity : 0;
				var max_quantity = parseInt( $(this).prev().data('max-quantity') );

				if( max_quantity !== 0 && quantity >= max_quantity )
				{
					quantity = max_quantity;
				}
				else
				{
					quantity++;
				}

				$(this).prev().val( quantity ).trigger('keyup');
			}).on('click', '.booknetic_service_extra_quantity_dec', function()
			{
				var quantity = parseInt( $(this).next().val() );
				quantity = quantity > 0 ? quantity : 1;
				quantity--;

				$(this).next().val( quantity ).trigger('keyup');
			}).on('keyup', '.booknetic_service_extra_quantity_input', function()
			{
				var quantity = parseInt( $(this).val() );
				if( !(quantity > 0) )
				{
					$(this).val('0');
					$(this).closest('.booknetic_service_extra_card').removeClass('booknetic_service_extra_card_selected');
				}
				else
				{
					$(this).closest('.booknetic_service_extra_card').addClass('booknetic_service_extra_card_selected');
				}
			}).on('click', '.booknetic_number_of_brought_customers_inc', function()
			{
				var quantity = parseInt( $(this).prev().val() );
				quantity = quantity > 0 ? quantity : 0;
				var max_quantity = parseInt( $(this).prev().data('max-quantity') );

				if( max_quantity !== 0 && quantity >= max_quantity )
				{
					quantity = max_quantity;
				}
				else
				{
					quantity++;
				}

				$(this).prev().val( quantity ).trigger('keyup');
			}).on('click', '.booknetic_number_of_brought_customers_dec', function()
			{
				var quantity = parseInt( $(this).next().val() );
				quantity = quantity > 0 ? quantity : 1;
				quantity--;

				$(this).next().val( quantity ).trigger('keyup');
			}).on('keyup', '.booknetic_number_of_brought_customers_quantity_input', function()
			{
				var quantity = parseInt( $(this).val() );
				if( !(quantity > 0) )
				{
					$(this).val('0');
				}
			});

			// Date & time stepi
			booking_panel_js.on('click', '.booknetic_calendar_days:not(.booknetic_calendar_empty_day)[data-date]', function()
			{
				var date = $(this).data('date');

				booking_panel_js.find(".booknetic_times_list").empty();

				var times = date in booknetic.calendarDateTimes['dates'] ? booknetic.calendarDateTimes['dates'][ date ] : [];
				var time_show_format = booknetic.time_show_format == 2 ? 2 : 1;

				for( var i = 0; i < times.length; i++ )
				{
					var time_badge = '';
					if( times[i]['number_of_customers'] > 0 && !( 'hide_available_slots' in booknetic.calendarDateTimes && booknetic.calendarDateTimes['hide_available_slots'] == 'on' ) )
					{
						time_badge = '<div class="booknetic_time_group_num">' + times[i]['number_of_customers'] + ' / ' + times[i]['max_capacity'] + '</div>';
					}

					booking_panel_js.find(".booknetic_times_list").append('<div data-time="' + times[i]['start_time'] + '" data-endtime="' + times[i]['end_time'] + '" data-date-original="' + times[i]['date'] + '"><div>' + times[i]['start_time_format'] + '</div>' + (time_show_format == 1 ? '<div>' + times[i]['end_time_format'] + '</div>' : '') + time_badge + '</div>');
				}

				booking_panel_js.find(".booknetic_times_list").scrollTop(0);
				booking_panel_js.find(".booknetic_times_list").getNiceScroll().resize();

				booking_panel_js.find(".booknetic_calendar_selected_day").removeClass('booknetic_calendar_selected_day');

				$(this).addClass('booknetic_calendar_selected_day');

				booking_panel_js.find(".booknetic_times_title").text( $(this).data('date-format') );

				if( booknetic.dateBasedService )
				{
					booking_panel_js.find(".booknetic_times_list > [data-time]:eq(0)").trigger('click');
				}
				else if( booknetic.isMobileView() )
				{
					$('html,body').animate({scrollTop: parseInt(booking_panel_js.find('.booknetic_time_div').offset().top) - 100}, 1000);
				}
			}).on('click', '.booknetic_prev_month', function ()
			{
				var month = booknetic.calendarMonth - 1;
				var year = booknetic.calendarYear;

				if( month < 0 )
				{
					month = 11;
					year--;
				}

				booknetic.nonRecurringCalendar( year, month );
			}).on('click', '.booknetic_next_month', function ()
			{
				var month = booknetic.calendarMonth + 1;
				var year = booknetic.calendarYear;

				if( month > 11 )
				{
					month = 0;
					year++;
				}

				booknetic.nonRecurringCalendar( year, month );
			}).on('click', '.booknetic_times_list > div', function ()
			{
				booking_panel_js.find('.booknetic_selected_time').removeClass('booknetic_selected_time');
				$(this).addClass('booknetic_selected_time');

				if( booking_panel_js.find('#booknetic_bring_someone_section').length == 0 )
				{
					booknetic.stepManager.goForward();
				}
			}).on('change', '#booknetic_bring_someone_checkbox', function(event)
			{
				if( $(this).is(':checked') )
				{
					booking_panel_js.find('.booknetic_number_of_brought_customers').removeClass('d-none');
				}
				else
				{
					booking_panel_js.find('.booknetic_number_of_brought_customers').addClass('d-none');
				}

				booknetic.niceScroll();
			});

			// Paymentle bagli
			booking_panel_js.on('click', '.booknetic_payment_method', function ()
			{
				booking_panel_js.find(".booknetic_payment_method_selected").removeClass('booknetic_payment_method_selected');
				$(this).addClass('booknetic_payment_method_selected');

				if( $(this).data('payment-type') == 'local' )
				{
					booking_panel_js.find(".booknetic_hide_on_local").removeClass('booknetic_hidden').fadeOut(100);
				}
				else
				{
					booking_panel_js.find(".booknetic_hide_on_local").removeClass('booknetic_hidden').fadeIn(100);
				}

			});

			// Information stepi

			booking_panel_js.on('keyup change', '[data-step-id=\'information\'] #bkntc_input_name,[data-step-id=\'information\'] #bkntc_input_surname,[data-step-id=\'information\'] #bkntc_input_email,[data-step-id=\'information\'] #bkntc_input_phone', function ()
			{
				$(this).removeClass('booknetic_input_error');
			}).on('click', '.booknetic_social_login_facebook, .booknetic_social_login_google', function ()
			{
				let login_window = window.open($(this).data('href'), 'booknetic_social_login', 'width=1000,height=700');

				let while_fn = function ()
				{
					var dataType = 'undefined';

					try {
						dataType = typeof login_window.booknetic_user_data;
					}
					catch (err){}

					if( dataType != 'undefined' )
					{
						if( booking_panel_js.find('#bkntc_input_surname').parent('div').hasClass('booknetic_hidden') )
						{
							booking_panel_js.find('#bkntc_input_name').val( login_window.booknetic_user_data['first_name'] + ' ' + login_window.booknetic_user_data['last_name'] );
						}
						else
						{
							booking_panel_js.find('#bkntc_input_name').val( login_window.booknetic_user_data['first_name'] );
							booking_panel_js.find('#bkntc_input_surname').val( login_window.booknetic_user_data['last_name'] );
						}

						booking_panel_js.find('#bkntc_input_email').val( login_window.booknetic_user_data['email'] );
						login_window.close();
						return;
					}

					if( !login_window.closed )
					{
						setTimeout( while_fn, 1000 );
					}
				}

				while_fn();
			});

			// Recurringle bagli eventler
			booking_panel_js.on('change', '.booknetic_day_of_week_checkbox', function ()
			{
				var activeFirstDay = booking_panel_js.find(".booknetic_times_days_of_week_area .booknetic_active_day").attr('data-day');

				var dayNum	= $(this).attr('id').replace('booknetic_day_of_week_checkbox_', ''),
					dayDIv	= booking_panel_js.find(".booknetic_times_days_of_week_area > [data-day='" + dayNum + "']");

				if( $(this).is(':checked') )
				{
					dayDIv.removeClass('booknetic_hidden').hide().slideDown(200, function ()
					{
						booknetic.niceScroll();
					}).addClass('booknetic_active_day');

					if( booknetic.dateBasedService )
					{
						dayDIv.find('.booknetic_wd_input_time').append('<option>00:00</option>').val('00:00');
					}
				}
				else
				{
					dayDIv.slideUp(200, function ()
					{
						booknetic.niceScroll();
					}).removeClass('booknetic_active_day');
				}

				booking_panel_js.find(".booknetic_times_days_of_week_area .booknetic_active_day .booknetic_copy_time_to_all").fadeOut( activeFirstDay > dayNum ? 100 : 0 );
				booking_panel_js.find(".booknetic_times_days_of_week_area .booknetic_active_day .booknetic_copy_time_to_all:first").fadeIn( activeFirstDay > dayNum ? 100 : 0 );

				if( booking_panel_js.find('.booknetic_day_of_week_checkbox:checked').length > 0 && !booknetic.dateBasedService )
				{
					booking_panel_js.find('.booknetic_times_days_of_week_area').slideDown(200);
				}
				else
				{
					booking_panel_js.find('.booknetic_times_days_of_week_area').slideUp(200);
				}

				booknetic.calcRecurringTimes();
			}).on('click', '.booknetic_date_edit_btn', function()
			{
				var tr		= $(this).closest('tr'),
					timeTd	= tr.children('td[data-time]'),
					time	= timeTd.data('time'),
					date1	= tr.children('td[data-date]').data('date');

				timeTd.children('.booknetic_time_span').html('<select class="form-control booknetic_time_select"></select>').css({'float': 'right', 'margin-right': '25px', 'width': '120px'}).parent('td').css({'padding-top': '7px', 'padding-bottom': '14px'});

				booknetic.select2Ajax( timeTd.find('.booknetic_time_select'), 'get_available_times', function()
				{
					return booknetic.formDataToObject( booknetic.ajaxParameters({date: date1}) );
				});

				$(this).closest('td').children('.booknetic_data_has_error').remove();
				$(this).remove();

				booknetic.niceScroll();

			}).on('click', '.booknetic_copy_time_to_all', function ()
			{
				var time = $(this).closest('.booknetic_active_day').find('.booknetic_wd_input_time').select2('data')[0];

				if( time )
				{
					var	timeId		= time['id'],
						timeText	= time['text'];

					booking_panel_js.find(".booknetic_active_day:not(:first)").each(function ()
					{
						$(this).find(".booknetic_wd_input_time").append( $('<option></option>').val( timeId ).text( timeText ) ).val( timeId ).trigger('change');
					});
				}

			}).on('keyup', '#booknetic_recurring_times', function()
			{
				var serviceData = booknetic.serviceData;

				if( !serviceData )
					return;

				var repeatType	=	serviceData['repeat_type'],
					start		=	booknetic.getSelected.recurringStartDate(),
					end			=	booknetic.getSelected.recurringEndDate(),
					times		=	$(this).val();

				if( start == '' || times == '' || times <= 0 )
					return;

				var frequency = (repeatType == 'daily') ? booking_panel_js.find('#booknetic_daily_recurring_frequency').val() : 1;

				if( !( frequency >= 1 ) )
				{
					frequency = 1;
					if( repeatType == 'daily' )
					{
						booking_panel_js.find('#booknetic_daily_recurring_frequency').val('1');
					}
				}

				var activeDays = {};
				if( repeatType == 'weekly' )
				{
					booking_panel_js.find(".booknetic_times_days_of_week_area > .booknetic_active_day").each(function()
					{
						activeDays[ $(this).data('day') ] = true;
					});

					if( $.isEmptyObject( activeDays ) )
					{
						return;
					}
				}
				else if( repeatType == 'monthly' )
				{
					var monthlyRecurringType = booking_panel_js.find("#booknetic_monthly_recurring_type").val();
					var monthlyDayOfWeek = booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").val();

					var selectedDays = booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").select2('val');

					if( selectedDays )
					{
						for( var i = 0; i < selectedDays.length; i++ )
						{
							activeDays[ selectedDays[i] ] = true;
						}
					}
				}

				var cursor = new Date( start );

				var c_times = 0;
				while( (!$.isEmptyObject( activeDays ) || repeatType == 'daily') && c_times < times )
				{
					var weekNum = cursor.getDay();
					var dayNumber = parseInt( cursor.getDate() );
					weekNum = weekNum > 0 ? weekNum : 7;
					var dateFormat = cursor.getFullYear() + '-' + booknetic.zeroPad( cursor.getMonth() + 1 ) + '-' + booknetic.zeroPad( cursor.getDate() );

					if( repeatType == 'monthly' )
					{
						if( ( monthlyRecurringType == 'specific_day' && typeof activeDays[ dayNumber ] != 'undefined' ) || booknetic.getMonthWeekInfo(cursor, monthlyRecurringType, monthlyDayOfWeek) )
						{
							if
							(
								// if is not off day for staff or service
								!( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
								// if is not holiday for staff or service
								typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
							)
							{
								c_times++;
							}
						}
					}
					else if
					(
						// if weekly repeat type then only selected days of week...
						( typeof activeDays[ weekNum ] != 'undefined' || repeatType == 'daily' ) &&
						// if is not off day for staff or service
						!( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
						// if is not holiday for staff or service
						typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
					)
					{
						c_times++;
					}

					cursor = new Date( cursor.getTime() + 1000 * 24 * 3600 * frequency );
				}

				cursor = new Date( cursor.getTime() - 1000 * 24 * 3600 * frequency );
				end = cursor.getFullYear() + '-' + booknetic.zeroPad( cursor.getMonth() + 1 ) + '-' + booknetic.zeroPad( cursor.getDate() );

				if( !isNaN( cursor.getFullYear() ) )
				{
					booking_panel_js.find('#booknetic_recurring_end').val( booknetic.convertDate( end, 'Y-m-d' ) );
				}
			}).on('keyup', '#booknetic_daily_recurring_frequency', booknetic.calcRecurringTimes
			).on('change', '#booknetic_monthly_recurring_type, #booknetic_monthly_recurring_day_of_week, #booknetic_monthly_recurring_day_of_month', booknetic.calcRecurringTimes
			).on('change', '#booknetic_monthly_recurring_type', function ()
			{
				if( $(this).val() == 'specific_day' )
				{
					booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").next('.select2').show();
					booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").next('.select2').hide();
				}
				else
				{
					booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").next('.select2').hide();
					booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").next('.select2').show();
				}
			}).on('change', '#booknetic_recurring_start, #booknetic_recurring_end', function ()
			{
				var startDate	= booknetic.getSelected.recurringStartDate(),
					endDate		= booknetic.getSelected.recurringEndDate();

				if( startDate == '' || ( ! $('#booknetic_recurring_end').is(':disabled') && endDate == '' ) )
					return;


				booknetic.ajax('get_day_offs', booknetic.ajaxParameters(), function( result )
				{
					booknetic.globalDayOffs = result['day_offs'];
					booknetic.globalTimesheet = result['timesheet'];

					result['disabled_days_of_week'].forEach(function( value, key )
					{
						booking_panel_js.find('#booknetic_day_of_week_checkbox_' + (parseInt(key)+1)).attr('disabled', value);
					});

					booknetic.calcRecurringTimes();
				});
			});

			$( window ).resize(function ()
			{
				booknetic.niceScroll();
			});

			var first_step_id = booking_panel_js.find('.booknetic_appointment_steps_body > .booknetic_appointment_step_element:not(.booknetic_menu_hidden)').eq(0).data('step-id');
			booknetic.stepManager.loadStep( first_step_id );

			booknetic.niceScroll();

			booknetic.fadeInAnimate('.booknetic_appointment_step_element:not(.booknetic_menu_hidden)');

			booking_panel_js.find(".booknetic_appointment_steps_footer").fadeIn(200);

			setTimeout(booknetic.stepManager.refreshStepNumbers, 450);

			if( 'google_recaptcha_site_key' in BookneticData )
			{
				grecaptcha.ready(function ()
				{
					booknetic.refreshGoogleReCaptchaToken();
				});
			}
		};

		$(".booknetic_appointment").each( ( i , v ) => initBookingPage( v ) )

		window.bookneticInitBookingPage = initBookingPage;

	});

})(jQuery);

