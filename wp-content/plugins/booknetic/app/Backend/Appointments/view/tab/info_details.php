<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var array $parameters
 */
?>

<div class="form-row">
    <div class="form-group col-md-4">
        <label><?php echo bkntc__('Location')?></label>
        <div class="form-control-plaintext"><?php echo htmlspecialchars( $parameters['info']['location_name'] )?></div>
    </div>
    <div class="form-group col-md-4">
        <label><?php echo bkntc__('Service')?></label>
        <div class="form-control-plaintext"><?php echo htmlspecialchars( $parameters['info']['service_name'] )?></div>
    </div>
    <div class="form-group col-md-4">
        <label><?php echo bkntc__('Date, time')?></label>
        <div class="form-control-plaintext"><?php echo $parameters['info']['duration'] >= 24*60 ? Date::datee( $parameters['info']['date'] ) : (Date::dateTime( $parameters['info']['date'] . ' ' . $parameters['info']['start_time'] ) . ' - ' . Date::time( Date::epoch( $parameters['info']['start_time'] ) + ($parameters['info']['duration'] + $parameters['info']['extras_duration']) * 60) )?></div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label class="text-primary"><?php echo bkntc__('Staff')?></label>
        <div class="form-control-plaintext"><?php echo Helper::profileCard($parameters['info']['staff_name'] , $parameters['info']['staff_profile_image'], $parameters['info']['staff_email'], 'Staff')?></div>
    </div>
</div>

<hr/>

<div class="form-row">
    <div class="form-group col-md-12">
        <label class="text-success"><?php echo bkntc__('Customers')?></label>
        <div class="form-control-plaintext">
            <div class="fs_data_table_wrapper">
                <?php
                $statuses = Helper::getAppointmentStatuses();
                foreach( $parameters['customers'] AS $customer )
                {
                    $status = $statuses[$customer['status']];
                    echo '<div class="per-customer-div cursor-pointer" data-load-modal="customers.info" data-parameter-id="'.(int)$customer['customer_id'].'">';
                    echo Helper::profileCard($customer['customers_first_name'] . ' ' . $customer['customers_last_name'], $customer['customers_profile_image'], $customer['customers_email'], 'Customers');
                    echo '<div class="appointment-status-icon ml-3" style="background-color: ' . htmlspecialchars( $status[ 'color' ] ) . '2b">
                                    <i style="color: ' . htmlspecialchars( $status[ 'color' ] ) . '" class="' . htmlspecialchars( $status[ 'icon' ] ) .  '"></i>
                                </div>';
                    echo '<span class="num_of_customers_span"><i class="fa fa-user"></i> ' . (int)$customer['number_of_customers'] . '</span>';


                    echo '<span>' . $customer['billing_full_name'] . (empty($customer['billing_phone']) ? '' : ' ('.$customer['billing_phone'].')') . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<hr/>

<div class="form-row">
    <div class="form-group col-md-12">
        <label><?php echo bkntc__('Note')?> </label>
        <div class="form-control-plaintext">
            <?php echo htmlspecialchars($parameters['info']->note)?>
        </div>
    </div>
</div>
