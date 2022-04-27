(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		booknetic.dataTable.actionCallbacks['info'] = function (ids)
		{
			booknetic.loadModal('info', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('edit', {'id': ids[0]});
		}

		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		}).on('click', '#fs_data_table_div .more-customers', function (e)
		{
			var id = $(this).closest('tr').data('id');

			$("#customers-list-popover").fadeIn(200);
			var panelWidt = $("#customers-list-popover").outerWidth();
			$("#customers-list-popover").css({top: (e.pageY + 15)+'px', left: (e.pageX - panelWidt / 2)+'px'});

			$("#customers-list-popover").after('<div class="lock-screen"></div>');

			$("#customers-list-popover .fs-popover-content").html('<div class="more_customers_loading">Loading...</div>');

			booknetic.ajax('appointments.get_customers_list', {appointment: id}, function(result )
			{
				$("#customers-list-popover .fs-popover-content").html( booknetic.htmlspecialchars_decode( result['html'] ) );
			});
		});

	});

})(jQuery);

