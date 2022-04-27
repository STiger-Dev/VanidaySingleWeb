<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

function customerTpl( $display = false, $cid = 0, $customerId = null, $customerName = null, $status = '', $number_of_customers = 1 )
{
    $statuses = Helper::getAppointmentStatuses();

    if ($display === false)
    {
        $defaultStatus = Helper::getDefaultAppointmentStatus();
        $defaultStatus = array_key_exists($defaultStatus, $statuses) ? $defaultStatus : array_keys($statuses)[0];
        $status = isset( $statuses[ $status ] ) ? $status : $defaultStatus;
    } else {

        if (!array_key_exists($status, $statuses))
        {
            $statuses[$status] = [
                'title' => $status,
                'icon' => 'fa fa-info',
                'key' => $status,
                'color' => 'gray'
            ];
        }

    }
    ?>
    <div class="form-row customer-tpl<?php echo ($display?'':' hidden')?>"<?php echo (' data-id="' . $cid . '"')?>>
        <div class="col-md-6">
            <div class="input-group">
                <select class="form-control input_customer">
                    <?php
                    echo is_null($customerId) ? '' : '<option value="' . (int)$customerId . '">' . htmlspecialchars($customerName) . '</option>';
                    ?>
                </select>
                <div class="input-group-prepend">
                    <button class="btn btn-outline-secondary btn-lg" type="button" data-load-modal="customers.add_new"><i class="fa fa-plus"></i></button>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex">
			<span class="customer-status-btn">
				<button class="btn btn-lg btn-outline-secondary" data-status="<?php echo $status?>" type="button" data-toggle="dropdown"><i class="<?php echo $statuses[$status]['icon']?>" style="color:<?php echo $statuses[$status]['color']?>"></i> <span class="c_status"><?php echo $statuses[$status]['title']?></span> <img src="<?php echo Helper::icon('arrow-down-xs.svg')?>"></button>
				<div class="dropdown-menu customer-status-panel">
					<?php
                    foreach ( $statuses AS $stName => $status )
                    {
                        echo '<a class="dropdown-item" href="#" data-status="' . $stName . '"><i class="' . $status['icon'] . '" style="color: ' . $status['color'] . ';"></i> ' . $status['title'] . '</a>';
                    }
                    ?>
				</div>
			</span>

            <div class="number_of_group_customers_span">
                <button class="btn btn-lg btn-outline-secondary number_of_group_customers" type="button" data-toggle="dropdown"><i class="fa fa-user "></i> <span class="c_number"><?php echo $number_of_customers?></span> <img src="<?php echo Helper::icon('arrow-down-xs.svg')?>"></button>
                <div class="dropdown-menu number_of_group_customers_panel"></div>
            </div>

            <span class="delete-customer-btn">
				<img src="<?php echo Helper::assets('icons/unsuccess.svg')?>">
			</span>
        </div>
    </div>
    <?php
}

/**
 * @var array $parameters
 */
?>
<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_location"><?php echo bkntc__('Location')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_location">
            <option value="<?php echo (int)$parameters['appointment']['location_id']?>" selected><?php echo htmlspecialchars($parameters['appointment']['location_name'])?></option>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label><?php echo bkntc__('Category')?> <span class="required-star">*</span></label>
        <?php
        foreach ( $parameters['categories'] AS $keyIndx => $categoryInf )
        {
            echo '<div class="mt-1"><select class="form-control input_category"><option value="' . (int)$categoryInf['id'] . '">' . htmlspecialchars($categoryInf['name']) . '</option></select></div>';
        }
        ?>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_service"><?php echo bkntc__('Service')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_service">
            <option value="<?php echo (int)$parameters['appointment']['service_id']?>" selected><?php echo htmlspecialchars($parameters['appointment']['service_name'])?></option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="input_staff"><?php echo bkntc__('Staff')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_staff">
            <option value="<?php echo (int)$parameters['appointment']['staff_id']?>" selected><?php echo htmlspecialchars($parameters['appointment']['staff_name'])?></option>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_date"><?php echo bkntc__('Date')?> <span class="required-star">*</span></label>
        <div class="inner-addon left-addon">
            <i><img src="<?php echo Helper::icon('calendar.svg')?>"/></i>
            <input class="form-control" id="input_date" placeholder="<?php echo bkntc__('Select...')?>" value="<?php echo Date::format(Helper::getOption('date_format', 'Y-m-d'), $parameters['appointment']['date'] )?>">
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="input_time"><?php echo bkntc__('Time')?> <span class="required-star">*</span></label>
        <div class="inner-addon left-addon">
            <i><img src="<?php echo Helper::icon('time.svg')?>"/></i>
            <select class="form-control" id="input_time">
                <option selected><?php echo ! empty( $parameters['appointment']['start_time'] ) ? Date::time( $parameters['appointment']['start_time'] ) : ''; ?></option>
            </select>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label><?php echo bkntc__('Customers')?> <span class="required-star">*</span></label>

        <div class="customers_area">
            <?php
            foreach ( $parameters['customers'] AS $customer)
            {
                customerTpl( true, $customer['id'], $customer['customer_id'], $customer['customer_name'], $customer['status'], $customer['number_of_customers'] );
            }
            ?>
        </div>

        <div class="add-customer-btn"><i class="fas fa-plus-circle"></i> <?php echo bkntc__('Add Customer')?></div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label><?php echo bkntc__('Note')?> </label>
        <textarea id='note' class="form-control" name="note" id="" cols="30" rows="10"><?php echo htmlspecialchars($parameters['appointment']->note)?></textarea>
    </div>
</div>

<?php echo customerTpl(); ?>