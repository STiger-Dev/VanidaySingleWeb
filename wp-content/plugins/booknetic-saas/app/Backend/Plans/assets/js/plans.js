(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		});

		booknetic.dataTable.actionCallbacks['set_as_default'] = function (ids)
		{
			let plan_id = ids[0];

			booknetic.ajax('set_as_default', {id: plan_id}, function ()
			{
				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		}

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('add_new', {'id': ids[0]});
		}

	});

})(jQuery);