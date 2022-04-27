<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Backend\Appointments\Helpers\TimeSlotService;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

?>

<div class="form-row">
	<div class="form-group col-md-12">
		<table class="booknetic_table_gray booknetic_dashed_border booknetic_recurring_table">
			<thead>
			<tr>
				<th><?php echo bkntc__('#')?></th>
				<th><?php echo bkntc__('DATE')?></th>
				<th<?php echo $parameters['appointmentObj']->isDateBasedService() ? ' class="booknetic_hidden"' : ''?>><?php echo bkntc__('TIME')?></th>
			</tr>
			</thead>
			<tbody id="booknetic_recurring_dates">
			<?php $index = 1;?>
			<?php foreach ( $parameters['appointments'] AS $timeSlot ): ?>
				<tr>
					<td><?php echo $index++?></td>
					<td data-date="<?php echo $timeSlot->getDate()?>"><?php echo $timeSlot->getDate( true )?></td>
					<td data-time="<?php echo $timeSlot->getTime()?>"<?php echo $parameters['appointmentObj']->isDateBasedService() ? ' class="booknetic_hidden"' : ''?>>
						<span class="booknetic_time_span"><?php echo $timeSlot->getTime( true )?></span>
						<?php if( ! $timeSlot->isBookable() ): ?>
							<span class="booknetic_data_has_error" title="<?php echo bkntc__('DATE')?>"><img src="<?php echo Helper::icon('warning_red.svg', 'front-end')?>"></span>
						<?php endif;?>
						<button type="button" class="booknetic_btn_secondary booknetic_date_edit_btn"><?php echo bkntc__('EDIT')?></button>
					</td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
	</div>
</div>

