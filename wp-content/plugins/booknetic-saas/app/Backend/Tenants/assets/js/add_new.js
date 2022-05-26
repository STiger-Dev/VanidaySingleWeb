(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$('.fs-modal').on('click', '#addTenantSave', function ()
		{
			let wp_user					= $('#input_wp_user').val(),
				wp_user_use_existing	= $("#input_wp_user_use_existing").val(),
				wp_user_password		= $("#input_wp_user_password").val(),
				email					= $("#input_email").val(),
				domain					= $("#input_domain").val(),
				full_name				= $("#input_full_name").val(),
				expires_in				= $("#input_expires_in").val(),
				plan_id				    = $("#input_plan_id").val(),
				custom_fields			= {};




			$("#tab_custom_fields [data-input-id][type!='checkbox'][type!='radio'], #tab_custom_fields [data-input-id][type='checkbox']:checked, #tab_custom_fields	 [data-input-id][type='radio']:checked").each(function()
			{
				var inputId		= $(this).data('input-id'),
					inputVal	= $(this).val();


				if( $(this).attr('type') == 'file' )
				{
					custom_fields[ inputId ] = $(this)[0].files[0];
				}
				else
				{
					if( typeof custom_fields[ inputId ] == 'undefined' )
					{
						custom_fields[ inputId ] = inputVal;
					}
					else
					{
						custom_fields[ inputId ] += ',' + inputVal;
					}
				}

			});


			var save_custom_data = [];
			$("#tab_custom_fields [data-save-custom-data]").each(function()
			{
				save_custom_data.push( $(this).data('save-custom-data') );
			});


			var data = new FormData();

			data.append('id', $(".fs-modal #add_new_JS").data('tenant-id'));
			data.append('wp_user', wp_user);
			data.append('wp_user_use_existing', wp_user_use_existing);
			data.append('wp_user_password', wp_user_password);
			data.append('full_name', full_name);
			data.append('email', email);
			data.append('domain', domain);
			data.append('expires_in', expires_in);
			data.append('plan_id', plan_id);

			data.append('save_custom_data', save_custom_data.join(','));


			for( var input_id in custom_fields)
			{
				data.append(`custom_fields[${input_id}]`, custom_fields[input_id]);
			}

			booknetic.ajax( 'save_tenant', data, function()
			{
				booknetic.modalHide($(".fs-modal"));

				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		}).on('change', '#input_wp_user_use_existing', function ()
		{
			if( $(this).val() === 'yes' )
			{
				$('[data-hide="existing_user"]').show();
				$('[data-hide="create_password"]').hide();
			}
			else
			{
				$('[data-hide="existing_user"]').hide();
				$('[data-hide="create_password"]').show();
			}
		}).on('change', '#input_wp_user', function ()
		{
			if( $(this).val() > 0 )
			{
				var email = $(this).children(':selected').data('email');
				var name = $(this).children(':selected').text();

				$('#input_name').val( name );
				$('#input_email').val( email );
			}
		});

		$('#input_wp_user_use_existing').trigger('change');

		$('#input_wp_user, #input_plan_id, #input_wp_user_use_existing, .custom-input-select2').select2({
			theme: 'bootstrap',
			allowClear: false
		});

		$("#input_expires_in, .form-control-date-input").datepicker({
			autoclose: true,
			format: 'yyyy-mm-dd',
			weekStart: weekStartsOn == 'sunday' ? 0 : 1
		});


		$('#tab_custom_fields').on('click', '.remove_custom_file_btn', function()
		{
			var placeholder = $(this).data('placeholder');

			$(this).parent().text( placeholder );
		});

	});

})(jQuery);