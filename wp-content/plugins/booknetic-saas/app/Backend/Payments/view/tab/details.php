<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */

?>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_tenant_id"><?php echo bkntcsaas__('Tenant')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_tenant_id">
            <?php
            if( (int)$parameters['billing']['id'] > 0 )
            {
                echo '<option value="' . (int)$parameters['billing']['tenant_id'] . '">' . $parameters['tenant'] . '</option>';
            }
            ?>
        </select>
    </div>
</div>


<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_plan_id"><?php echo bkntcsaas__('Plan')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_plan_id">
            <?php
            foreach ( Plan::fetchAll() AS $plan )
            {
                ?>
                <option value="<?php echo (int)$plan['id']?>" <?php echo $plan['id'] == $parameters['billing']['plan_id'] ? ' selected' : ''?>><?php echo htmlspecialchars($plan['name'])?></option>
                <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="input_amount"><?php echo bkntcsaas__('Amount')?> ( <?php echo htmlspecialchars(Helper::currencySymbol())?> ) <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_amount" value="<?php echo htmlspecialchars($parameters['billing']['amount'])?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_payment_method"><?php echo bkntcsaas__('Payment method')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_payment_method">
            <option value="offline"<?php echo $parameters['billing']['payment_method']=='offline' ? ' selected' : ''?>><?php echo bkntcsaas__('Offline')?></option>
            <option value="paypal"<?php echo $parameters['billing']['payment_method']=='paypal' ? ' selected' : ''?>><?php echo bkntcsaas__('Paypal')?></option>
            <option value="credit_card"<?php echo $parameters['billing']['payment_method']=='credit_card' ? ' selected' : ''?>><?php echo bkntcsaas__('Credit card')?></option>
        </select>
    </div>

    <div class="form-group col-md-6">
        <label for="input_payment_cycle"><?php echo bkntcsaas__('Payment cycle')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_payment_cycle">
            <option value="monthly"><?php echo bkntcsaas__('Monthly')?></option>
            <option value="annually"<?php echo $parameters['billing']['payment_cycle']=='annually' ? ' selected' : ''?>><?php echo bkntcsaas__('Annually')?></option>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_created_at"><?php echo bkntcsaas__('Payment date')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_created_at" value="<?php echo Date::dateTimeSQL( $parameters['billing']['created_at'] )?>">
    </div>
</div>
