<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @param int $id
 * @param string $name
 * @param int $duration
 * @param int $price
 * @param int $max_quantity
 * @param int $is_active
 * @var mixed $parameters

 */

function extrasTpl($id = 0, $name = '', $duration = 0, $price = 0, $max_quantity = 0, $is_active = 1 )
{
    ?>
    <div class="form-row extra_row dashed-border" data-id="<?php echo (int)$id?>" data-active="<?php echo (int)$is_active ?>">
        <div class="form-group col-md-4">
            <label class="text-primary"><?php echo bkntc__('Name')?>:</label>
            <div class="form-control-plaintext" data-tag="name"><?php echo htmlspecialchars($name)?></div>
        </div>
        <div class="form-group col-md-3">
            <label><?php echo bkntc__('Duration')?>:</label>
            <div class="form-control-plaintext" data-tag="duration"><?php echo !$duration ? '-' : Helper::secFormat( $duration * 60 )?></div>
        </div>
        <div class="form-group col-md-2">
            <label><?php echo bkntc__('Price')?>:</label>
            <div class="form-control-plaintext" data-tag="price"><?php echo Helper::price( $price , false )?></div>
        </div>
        <div class="form-group col-md-3">
            <label><?php echo bkntc__('Max. quantity')?>:</label>
            <div class="form-control-plaintext" data-tag="max_quantity"><?php echo (int)$max_quantity?></div>
        </div>
        <div class="extra_actions">
            <img src="<?php echo Helper::icon('edit.svg', 'Services')?>" class="edit_extra">
            <img src="<?php echo Helper::icon('hide.svg', 'Services')?>" class="hide_extra">
            <img src="<?php echo Helper::icon('copy.svg', 'Services')?>" class="copy_extra" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
            <div class="dropdown-menu dropdown-menu-right row-actions-area">
                <button class="dropdown-item copy_to_all_services" type="button"><?php echo bkntc__('Copy to all services')?></button>
                <button class="dropdown-item copy_to_parent_services" type="button"><?php echo bkntc__('Copy to the same category services')?></button>
            </div>
            <img src="<?php echo Helper::icon('remove.svg', 'Services')?>" class="delete_extra">
        </div>
    </div>
    <?php
}

?>

<div id="extra_list_area">

    <?php
    foreach ($parameters['extras'] AS $extraInf )
    {
        extrasTpl( $extraInf['id'], $extraInf['name'], $extraInf['duration'], $extraInf['price'], $extraInf['max_quantity'], $extraInf['is_active'] );
    }
    ?>

</div>

<button type="button" class="btn btn-success" id="new_extra_btn"><?php echo bkntc__('NEW EXTRA')?></button>

<div id="new_extra_panel" class="hidden">

    <div class="extra_picture_div">
        <div class="extra_picture">
            <input type="file" id="input_image2">
            <div class="img-circle1"><img src="<?php echo Helper::profileImage('', 'Services')?>" data-src="<?php echo Helper::profileImage('', 'Services')?>"></div>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="input_extra_name"><?php echo bkntc__('Name')?> <span class="required-star">*</span></label>
            <input class="form-control required" id="input_extra_name" maxlength="100">
        </div>
        <div class="form-group col-md-6">
            <label for="input_extra_max_quantity"><?php echo bkntc__('Max. quantity')?></label>
            <input type="number" class="form-control" id="input_extra_max_quantity">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="input_extra_price"><?php echo bkntc__('Price')?> <span class="required-star">*</span></label>
            <input class="form-control required" id="input_extra_price">
        </div>
        <div class="form-group col-md-6">
            <label>&nbsp;</label>
            <div class="form-control-checkbox">
                <label for="input_extra_hide_price"><?php echo bkntc__('Hide price in booking panel:')?></label>
                <div class="fs_onoffswitch">
                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_extra_hide_price">
                    <label class="fs_onoffswitch-label" for="input_extra_hide_price"></label>
                </div>
            </div>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="input_extra_duration"><?php echo bkntc__('Duration')?></label>
            <select class="form-control" id="input_extra_duration"></select>
        </div>
        <div class="form-group col-md-6">
            <label>&nbsp;</label>
            <div class="form-control-checkbox">
                <label for="input_extra_hide_duration"><?php echo bkntc__('Hide duration in booking panel:')?></label>
                <div class="fs_onoffswitch">
                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_extra_hide_duration">
                    <label class="fs_onoffswitch-label" for="input_extra_hide_duration"></label>
                </div>
            </div>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <button type="button" class="btn btn-default new_extra_panel_cancel_btn mr-2"><?php echo bkntc__('CANCEL')?></button>
            <button type="button" class="btn btn-success new_extra_panel_save_btn"><?php echo bkntc__('SAVE EXTRA')?></button>
        </div>
    </div>

</div>

<div class="hidden">
    <?php echo extrasTpl(); ?>
</div>