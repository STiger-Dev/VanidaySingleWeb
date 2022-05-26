<?php

defined( 'ABSPATH' ) or die();

/**
 * @var array $parameters
 */
use BookneticSaaS\Providers\Helpers\Helper;
?>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_name"><?php echo bkntcsaas__('Name')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_name" value="<?php echo htmlspecialchars($parameters['plan']['name'])?>">
    </div>
    <div class="form-group col-md-6">
        <label for="input_ribbon_text"><?php echo bkntcsaas__('Bookmark')?></label>
        <input type="text" class="form-control" id="input_ribbon_text" maxlength="15" value="<?php echo htmlspecialchars($parameters['plan']['ribbon_text'])?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6 position-relative">
        <label for="input_color"><?php echo bkntcsaas__('Color')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_color" value="<?php echo htmlspecialchars($parameters['plan']['color'])?>">
        <span class="plan_color"></span>
    </div>
    <div class="form-group col-md-6">
        <label for="input_order_by"><?php echo bkntcsaas__('Order number')?></label>
        <input type="text" class="form-control" id="input_order_by" value="<?php echo htmlspecialchars($parameters['plan']['order_by'])?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_monthly_price"><?php echo bkntcsaas__('Monthly price')?> (<?php echo Helper::currencySymbol()?>)</label>
        <input type="text" class="form-control" id="input_monthly_price" value="<?php echo htmlspecialchars($parameters['plan']['monthly_price'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
    <div class="form-group col-md-6">
        <label for="input_monthly_price_discount"><?php echo bkntcsaas__('Discount for first month')?> (%)</label>
        <input type="text" class="form-control" id="input_monthly_price_discount" value="<?php echo htmlspecialchars($parameters['plan']['monthly_price_discount'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_annually_price"><?php echo bkntcsaas__('Annually price')?> (<?php echo Helper::currencySymbol()?>)</label>
        <input type="text" class="form-control" id="input_annually_price" value="<?php echo htmlspecialchars($parameters['plan']['annually_price'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
    <div class="form-group col-md-6">
        <label for="input_annually_price_discount"><?php echo bkntcsaas__('Discount for first year')?> (%)</label>
        <input type="text" class="form-control" id="input_annually_price_discount" value="<?php echo htmlspecialchars($parameters['plan']['annually_price_discount'])?>"<?php echo empty($parameters['plan']['stripe_product_data']) ? '' : ' readonly'?>>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <div class="form-control-checkbox">
            <label for="input_is_active"><?php echo bkntcsaas__('Hidden plan')?></label>
            <div class="fs_onoffswitch">
                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_is_active"<?php echo $parameters['plan']['is_active']?'':' checked'?>>
                <label class="fs_onoffswitch-label" for="input_is_active"></label>
            </div>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_description"><?php echo bkntcsaas__('Description')?></label>
        <textarea class="form-control" id="input_description"><?php echo htmlspecialchars($parameters['plan']['description'])?></textarea>
    </div>
</div>