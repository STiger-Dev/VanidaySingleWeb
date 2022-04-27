(function ($)
{
    "use strict";

    $(document).ready(function()
    {

        booknetic.dataTable.onLoad( function (){
                $('#fs_data_table_div').find('table tr').each(function () {
                    if( $(this).children('td:last-child').children('button').length === 0 && $(this).children('td:last-child').find('.actions_btn').length !== 0)
                    {
                        $(this).children('td:last-child').prepend('<button type="button" class="edit_action_btn btn btn-light-success">'+ booknetic.__('Edit') +'</button>');
                    }
                });
            }
        )


        $(document).on('click', '#addBtn', function ()
        {
            booknetic.loadModal('add_new', {});
        }).on('click', '#fs_data_table_div .edit_action_btn', function()
        {
            location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=workflow&action=edit&workflow_id=' + $(this).closest('tr').data('id');
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

