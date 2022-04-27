(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(document).on('click', '#upcomming_appointments .more-customers', function (e)
		{
			var id = $(this).closest('tr').data('id');

			$("#customers-list-popover").fadeIn(200);
			var panelWidt = $("#customers-list-popover").outerWidth();
			$("#customers-list-popover").css({top: (e.pageY + 15)+'px', left: (e.pageX - panelWidt / 2)+'px'});

			$("#customers-list-popover").after('<div class="lock-screen"></div>');

			$("#customers-list-popover .fs-popover-content").html('<div class="more_customers_loading">' + booknetic.__('loading') + '</div>');

			booknetic.ajax('appointments.get_customers_list', {appointment: id}, function(result )
			{
				$("#customers-list-popover .fs-popover-content").html( booknetic.htmlspecialchars_decode( result['html'] ) );
			});
		}).on('click', '#date_buttons .date_button', function ()
		{
			if( $(this).hasClass('active_btn') )
				return;


			$("#date_buttons .date_button.active_btn").removeClass('active_btn');
			$(this).addClass('active_btn');

			var type = $(this).data('type');

			if( type == 'custom' )
			{
				$(".custom_date_range").parent().fadeIn(200);

				return;
			}
			else
			{
				$(".custom_date_range").parent().fadeOut(200);
			}

			loadStatisticData( type );
		});

		dateFormat = dateFormat.replace('Y', 'YYYY')
			.replace('m', 'MM')
			.replace('d', 'DD');

		$(".custom_date_range").daterangepicker({
			opens: 'left',
			locale: {
				format: dateFormat, // "YYYY-MM-DD",
				separator: " - ",
				applyLabel: booknetic.__('Apply'),
				cancelLabel: booknetic.__('Cancel'),
				fromLabel: booknetic.__('From'),
				toLabel: booknetic.__('To'),
				customRangeLabel: "Custom",
				daysOfWeek: [
					booknetic.__("Sun"),
					booknetic.__("Mon"),
					booknetic.__("Tue"),
					booknetic.__("Wed"),
					booknetic.__("Thu"),
					booknetic.__("Fri"),
					booknetic.__("Sat")
				],
				monthNames: [
					booknetic.__("January"),
					booknetic.__("February"),
					booknetic.__("March"),
					booknetic.__("April"),
					booknetic.__("May"),
					booknetic.__("June"),
					booknetic.__("July"),
					booknetic.__("August"),
					booknetic.__("September"),
					booknetic.__("October"),
					booknetic.__("November"),
					booknetic.__("December")
				],
				firstDay: 1
			},
			startDate: new Date(),
			endDate: new Date(),
			cancelClass: "btn-outline-secondary"
		}, function(start, end, label)
		{
			loadStatisticData( 'custom', start.format(dateFormat), end.format(dateFormat) );
		});

		function loadStatisticData( type, startDate, endDate )
		{

			booknetic.ajax('Dashboard.get_stat', {type: type, start: startDate, end: endDate}, function( result )
			{
				$("#statistic-boxes-area .box-number-div[data-stat='appointments']").text( result['appointments'] );
				$("#statistic-boxes-area .box-number-div[data-stat='duration']").text( result['duration'] );
				$("#statistic-boxes-area .box-number-div[data-stat='revenue']").text( result['revenue'] );
				$("#statistic-boxes-area .box-number-div[data-stat='customers']").text( result['customers'] );

				$('.dashboard-appointments').each(function () {
					let element = $(this).find('.appointment-stats');
					let status = element.attr('data-stat').replace('status-','');
					element.text( result['count_by_status'][status] !== undefined ? result['count_by_status'][status]['count'] : 0);
				})
			});

		}

		loadStatisticData('today');

	});

})(jQuery);