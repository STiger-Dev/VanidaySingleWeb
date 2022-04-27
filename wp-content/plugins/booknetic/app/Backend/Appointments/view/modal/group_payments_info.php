<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Backend\Appointments\Helpers\AppointmentCustomerSmartObject;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 * @var mixed $_mn
 */

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/info.css', 'Payments')?>">

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-credit-card "></i></div>
    <div class="title-text"><?php echo bkntc__('Payments')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">

        <div class="bordered-light-portlet">
            <div class="form-row">
                <div class="col-md-3">
                    <label><?php echo bkntc__('Staff:')?></label>
                    <div class="form-control-plaintext text-primary">
                        <?php echo htmlspecialchars( $parameters['staff_name'] )?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label><?php echo bkntc__('Location:')?></label>
                    <div class="form-control-plaintext">
                        <?php echo htmlspecialchars( $parameters['location_name'] )?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label><?php echo bkntc__('Service:')?></label>
                    <div class="form-control-plaintext">
                        <?php echo htmlspecialchars( $parameters['service_name'] )?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label><?php echo bkntc__('Date, time:')?></label>
                    <div class="form-control-plaintext">
                        <?php echo $parameters['appointment']['duration'] >= 1440 ? Date::datee( $parameters['appointment']['date'] ) : Date::dateTime( $parameters['appointment']['date'] . ' ' . $parameters['appointment']['start_time'] )?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row mt-4">
            <div class="form-group col-md-12">
                <div class="fs_data_table_wrapper">
                    <table class="table-gray-2 dashed-border">
                        <thead>
                        <tr>
                            <th><?php echo bkntc__('CUSTOMER')?></th>
                            <th class="text-center"><?php echo bkntc__('TOTAL')?></th>
                            <th class="text-center"><?php echo bkntc__('PAID')?></th>
                            <th class="text-center"><?php echo bkntc__('DUE')?></th>
                            <th class="text-center pr-1"><?php echo bkntc__('METHOD')?></th>
                            <th class="text-center"><?php echo bkntc__('STATUS')?></th>
                            <th class="text-center"><?php echo bkntc__('ACTIONS')?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($parameters['appointment_customers'] as $appointment_customer) { $parameters['info'] = AppointmentCustomerSmartObject::load($appointment_customer['id']); ?>
                        <?php
                        echo '<tr data-customer-id="' . (int)$parameters['info']->getInfo()->customer_id . '" data-id="' . (int)$parameters['info']->getId() . '">';
                        echo '<td>' . Helper::profileCard($parameters['info']->getCustomerInf()->full_name, $parameters['info']->getCustomerInf()->profile_image, $parameters['info']->getCustomerInf()->email, 'Customers') . '</td>';
                        echo '<td class="text-center">' . Helper::price( $parameters['info']->getTotalAmount() ) . '</td>';
                        echo '<td class="text-center">' . Helper::price( $parameters['info']->getRealPaidAmount() ) . '</td>';
                        echo '<td class="text-center">' . Helper::price( $parameters['info']->getDueAmount() ) . '</td>';
                        echo '<td class="text-center">' . Helper::paymentMethod( $parameters['info']->getInfo()->payment_method ) . '</td>';
                        echo '<td class="text-center"><span class="payment-status-' . htmlspecialchars($parameters['info']->getInfo()->payment_status) . '"></span></td>'; ?>
                               <td class="text-center"><button type="button" class="btn btn-outline-secondary mr-2" data-load-modal="payments.info" data-parameter-id="<?php echo $appointment_customer['id']?>" data-parameter-mn2="<?php echo $_mn?>"><?php echo bkntc__('INFO')?></button></td>

                        <?php
                        echo '</tr>';
                        ?>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE')?></button>
</div>
