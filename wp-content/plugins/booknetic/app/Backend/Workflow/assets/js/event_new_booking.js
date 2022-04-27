(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        $('.fs-modal').on('click', '#eventSettingsSave', function ()
        {
            var locations			= $("#input_locations").val(),
                services			= $("#input_services").val(),
                staffs			    = $("#input_staff").val(),
                locale			    = $("#input_locale").val();


            var data = new FormData();

            data.append('id', currentWorkflowID);
            data.append('locations', JSON.stringify( locations ));
            data.append('services', JSON.stringify( services ));
            data.append('staffs', JSON.stringify( staffs ));
            data.append('locale', locale);

            booknetic.ajax( 'workflow_events.event_new_booking_save', data, function()
            {
                booknetic.modalHide($(".fs-modal"));
            });
        });

        booknetic.select2Ajax( $(".fs-modal #input_locations"), 'workflow_events.get_locations');
        booknetic.select2Ajax( $(".fs-modal #input_services"), 'workflow_events.get_services');
        booknetic.select2Ajax( $(".fs-modal #input_staff"), 'workflow_events.get_staffs');

    });

})(jQuery);