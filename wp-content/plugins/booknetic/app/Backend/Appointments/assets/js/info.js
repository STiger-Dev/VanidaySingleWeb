(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		$(".fs-modal").on('click', '.delete-btn', function ()
		{
			booknetic.confirm('Are you really want to delete?', 'danger', 'trash', function()
			{
				var ajaxData = {
					'fs-data-table-action': 'delete',
					'ids': [ $('#add_new_JS_info1').data('appointment-id') ]
				};

				$.post(location.href.replace( /module=\w+/g , 'module=appointments'), ajaxData, function ( result )
				{
					if( $("#fs_data_table_div").length > 0 )
					{
						booknetic.dataTable.reload( $("#fs_data_table_div") );

						booknetic.toast('Deleted!', 'success', 5000);

						booknetic.modalHide($(".fs-modal"));
					}
					else
					{
						location.reload();
					}
				});
			});
		});

	});

})(jQuery);