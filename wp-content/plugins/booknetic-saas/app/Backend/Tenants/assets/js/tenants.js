(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('add_new', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['billing_history'] = function (ids)
		{
			location.href = '?page=booknetic-saas&module=payments&tenant_id=' + ids[0];
		}

		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		});

		var js_parameters = $('#tenant-js12394610');

		if( js_parameters.data('edit') > 0 )
		{
			booknetic.loadModal('add_new', {'id': js_parameters.data('edit')});
		}

	});

})(jQuery);