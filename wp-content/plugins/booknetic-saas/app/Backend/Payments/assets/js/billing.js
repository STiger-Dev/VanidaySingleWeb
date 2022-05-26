(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(document).on('click', '#addBtn', function ()
		{
			let tenant_id = location.href.match(/tenant_id\=([0-9]+)/);
			tenant_id = tenant_id ? tenant_id[1] : 0;

			booknetic.loadModal('add_new', {tenant_id: tenant_id});
		});

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('add_new', {'id': ids[0]});
		}


	});

})(jQuery);