(function($)
{
	"use strict";

	function __( key )
	{
		return key in BookneticSaaSData.localization ? BookneticSaaSData.localization[ key ] : key;
	}

	let bookneticSaaS = {

		options: {
			'templates': {
				'loader': '<div class="bookneticsaas-loader"></div>',
				'toast': '<div id="booknetic-toastr"><div class="booknetic-toast-img"><img></div><div class="booknetic-toast-details"><span class="booknetic-toast-description"></span></div><div class="booknetic-toast-remove"><i class="fa fa-times"></i></div></div>'
			}
		},

		localization: {
			month_names: [ __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December') ],
			day_of_week: [ __('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun') ] ,
		},

		toastTimer: 0,


		parseHTML: function ( html )
		{
			let range = document.createRange();
			return range.createContextualFragment( html );
		},

		loading: function ( onOff )
		{
			$('body .bookneticsaas-loader').remove();

			if( typeof onOff === 'undefined' || onOff )
			{
				$('body').append(bookneticSaaS.options.templates.loader);
			}
		},



		datePickerFormat: function()
		{
			if( BookneticSaaSData.date_format == 'd-m-Y' )
			{
				return 'dd-mm-yyyy';
			}
			else if( BookneticSaaSData.date_format == 'm/d/Y' )
			{
				return 'mm/dd/yyyy';
			}
			else if( BookneticSaaSData.date_format == 'd/m/Y' )
			{
				return 'dd/mm/yyyy';
			}
			else if( BookneticSaaSData.date_format == 'd.m.Y' )
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
				to = bookneticSaaS.datePickerFormat();
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



		getCustomFields: function()
		{
			var customFields = {};

			$("#booknetic_tenant_custom_form [data-input-id][type!='checkbox'][type!='radio'], #booknetic_tenant_custom_form [data-input-id][type='checkbox']:checked, #booknetic_tenant_custom_form [data-input-id][type='radio']:checked").each(function()
			{
				var inputId		= $(this).data('input-id'),
					inputVal	= $(this).val();

				if( !inputVal )
				{
					inputVal = '';
				}

				if( inputVal != '' && $(this).data('isdatepicker') )
				{
					inputVal = bookneticSaaS.convertDate( inputVal, bookneticSaaS.datePickerFormat(), 'Y-m-d' );
				}

				if( $(this).attr('type') == 'file' )
				{
					customFields[ inputId ] = $(this)[0].files[0] ? $(this)[0].files[0] : 'booknetic_appointment_finished_with_error';
				}
				else
				{
					if( typeof customFields[ inputId ] == 'undefined' )
					{
						customFields[ inputId ] = inputVal;
					}
					else
					{
						customFields[ inputId ] += ',' + inputVal;
					}
				}
			});

			return customFields;
		},



		initDatepicker: function ( el )
		{
			bookneticdatepicker( el[0], {
				formatter: function (input, date, instance)
				{
					var val = date.getFullYear() + '-' + bookneticSaaS.zeroPad( date.getMonth() + 1 ) + '-' + bookneticSaaS.zeroPad( date.getDate() );
					input.value = bookneticSaaS.convertDate( val, 'Y-m-d' );
				},
				startDay: BookneticSaaSData.week_starts_on == 'sunday' ? 0 : 1,
				customDays: [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
				customMonths: [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
				onSelect: function( input )
				{
					$(input.el).trigger('change');
				}
			});
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
			var OPTS ={
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
					this.toast( 'Error!', 'unsuccess' );
					return false;
				}
			}

			if( typeof res['status'] == 'undefined' )
			{
				this.toast( 'Error!', 'unsuccess' );
				return false;
			}

			if( res['status'] == 'error' )
			{
				this.toast( typeof res['error_msg'] == 'undefined' ? 'Error!' : res['error_msg'], 'unsuccess' );
				return false;
			}

			if( res['status'] == 'ok' )
				return true;

			// else

			this.toast( 'Error!', 'unsuccess' );
			return false;
		},

		ajax: function ( action , params , func , loading, fnOnError )
		{
			loading = loading === false ? false : true;

			if( loading )
			{
				bookneticSaaS.loading(true);
			}

			if( params instanceof FormData)
			{
				params.append('action', 'bkntcsaas_' + action);
			}
			else
			{
				params['action'] = 'bkntcsaas_' + action;
			}

			var ajaxObject =
			{
				url: BookneticSaaSData.ajax_url,
				method: 'POST',
				data: params,
				success: function ( result )
				{
					if( loading )
					{
						bookneticSaaS.loading( 0 );
					}

					if( bookneticSaaS.ajaxResultCheck( result, fnOnError ) )
					{
						try
						{
							result = JSON.parse(result);
						}
						catch(e)
						{

						}
						if( typeof func == 'function' )
							func( result );
					}
					else if( typeof fnOnError == 'function' )
					{
						fnOnError();
					}
				},
				error: function (jqXHR, exception)
				{
					if( loading )
					{
						bookneticSaaS.loading( 0 );
					}

					bookneticSaaS.toast( jqXHR.status + ' error!' );

					if( typeof fnOnError == 'function' )
					{
						fnOnError();
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
			params['action'] = 'bkntcsaas_' + action;

			select.select2({
				theme: 'bootstrap',
				placeholder: __('select'),
				allowClear: true,
				ajax: {
					url: BookneticSaaSData.ajax_url,
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
						if( bookneticSaaS.ajaxResultCheck( result ) )
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
			return n.padStart(p, '0');
		},

		toast: function(title , type , duration )
		{
			$("#booknetic-toastr").remove();

			if( this.toastTimer )
				clearTimeout(this.toastTimer);

			$("body").append(this.options.templates.toast);

			$("#booknetic-toastr").hide().fadeIn(300);

			type = type === 'unsuccess' ? 'unsuccess' : 'success';

			$("#booknetic-toastr .booknetic-toast-img > img").attr('src', BookneticSaaSData.assets_url + 'icons/' + type + '.svg');

			$("#booknetic-toastr .booknetic-toast-description").text(title);

			duration = typeof duration != 'undefined' ? duration : 1000 * ( title.length > 48 ? parseInt(title.length / 12) : 4 );

			this.toastTimer = setTimeout(function()
			{
				$("#booknetic-toastr").fadeOut(200 , function()
				{
					$(this).remove();
				});
			} , typeof duration != 'undefined' ? duration : 4000);
		},

	};

	$(document).ready( function()
	{

		bookneticSaaS.select2Ajax( $('#booknetic_tenant_custom_form').find(".form-control.custom-input-select2"), 'get_tenant_custom_field_choices', function(input )
		{
			var inputId = input.data('input-id');

			return {
				input_id: inputId
			}
		});


		$(document).on('click', '.form-control[type="file"] ~ .form-control', function( e )
		{

			if( !$(e.target).is('a[href]') )
			{
				$(this).prev('.form-control[type="file"]').click();
			}

		}).on('change', '.form-control[type="file"]', function (e)
		{
			var fileName = e.target.files[0].name;

			$(this).next().text( fileName );
		});



		$(".form-control-date-input").each(function()
		{
			$(this).attr('type', 'text').data('isdatepicker', true);

			bookneticSaaS.initDatepicker( $(this) );
		});


		$(document).on('click', '.bookneticsaas_signup_btn', function ()
		{
			let form        = $(this).closest('.bookneticsaas_signup'),
				full_name	= form.find('#bookneticsaas_full_name').val(),
				email		= form.find('#bookneticsaas_email').val(),
				password	= form.find('#bookneticsaas_password').val();

			bookneticSaaS.ajax('signup', {
				full_name: full_name,
				email: email,
				password: password
			}, function ( result )
			{
				form.find('.bookneticsaas_step_1').hide();
				form.find('.bookneticsaas_resend_activation').hide();
				form.find('.bookneticsaas_step_2').fadeIn(200);
				setTimeout( function ()
				{
					form.find('.bookneticsaas_resend_activation').slideDown(200);
				}, 60000);
			});

			return false;
		}).on('click', '.bookneticsaas_continue_btn', function ()
		{
			let form    = $(this).closest('.bookneticsaas_signup'),
				domain  = form.find('#bookneticsaas_domain').val();

			bookneticSaaS.ajax('complete_signup', {
				domain: domain,
				token: form.data('token')
			}, function ( result )
			{
				form.find('.bookneticsaas_step_1').fadeOut(200, function ()
				{
					form.find('.bookneticsaas_step_2').fadeIn(200);
				});
			});
		}).on('click', '.bookneticsaas_company_image_border', function ()
		{
			$(this).closest('.bookneticsaas_signup').find('#bookneticsaas_company_image_input').click();
		}).on('change', '#bookneticsaas_company_image_input', function ()
		{
			if( $(this)[0].files && $(this)[0].files[0] )
			{
				let form    = $(this).closest('.bookneticsaas_signup'),
					reader  = new FileReader();

				reader.onload = function(e)
				{
					form.find('.bookneticsaas_company_image_border > img').attr('src', e.target.result);
				}

				reader.readAsDataURL( $(this)[0].files[0] );
			}
		}).on('click', '.bookneticsaas_complete_signup_btn', function ()
		{
			let form            = $(this).closest('.bookneticsaas_signup'),
				company_name	= form.find('#bookneticsaas_company_name').val(),
				address			= form.find('#bookneticsaas_address').val(),
				phone_number	= form.find('#bookneticsaas_phone_number').val(),
				website			= form.find('#bookneticsaas_website').val(),
				company_image	= form.find("#bookneticsaas_company_image_input")[0].files[0];

			let data = new FormData();

			data.append('company_name', company_name);
			data.append('address', address);
			data.append('phone_number', phone_number);
			data.append('website', website);
			data.append('company_image', company_image);
			data.append('token', form.data('token'));

			let custom_fields = bookneticSaaS.getCustomFields();

			for( var n in custom_fields )
			{
				data.append( 'custom_fields['+n+']', custom_fields[n] );
			}

			bookneticSaaS.ajax('complete_signup_company_details', data, function ( result )
			{
				form.find('.bookneticsaas_step_2').fadeOut(200, function ()
				{
					form.find('.bookneticsaas_step_3').fadeIn(200);
				});
			});
		}).on('click', '.bookneticsaas_resend_activation_link', function ()
		{
			let form        = $(this).closest('.bookneticsaas_signup'),
				email		= form.find('#bookneticsaas_email').val(),
				password	= form.find('#bookneticsaas_password').val();

			bookneticSaaS.ajax('resend_activation_link', { email: email, password: password }, function ( result )
			{
				form.find('.bookneticsaas_resend_activation').hide();

				setTimeout( function ()
				{
					form.find('.bookneticsaas_resend_activation').slideDown(200);
				}, 60000);
			});
		}).on('submit', '.bookneticsaas_form', function ()
		{
			$(this).find('.bookneticsaas_signup_btn').click();
			return false;
		});

	});

})(jQuery);

