<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;

?>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_wp_user"><?php echo bkntcsaas__('Assign to WordPress user')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_wp_user_use_existing">
            <option value="no"><?php echo bkntcsaas__('Create new WordPress user')?></option>
            <option value="yes" <?php echo ($parameters['tenant']['user_id'] > 0 ? ' selected' : '')?>><?php echo bkntcsaas__('Use existing WordPress user')?></option>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6" data-hide="existing_user">
        <label for="input_wp_user"><?php echo bkntcsaas__('Select WordPress user')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_wp_user">
            <?php
            foreach ( $parameters['users'] AS $user )
            {
                ?>
                <option value="<?php echo (int)$user->ID?>" <?php echo $user->ID == $parameters['tenant']['user_id'] ? ' selected' : ''?> data-email="<?php echo htmlspecialchars( $user->user_email )?>"><?php echo htmlspecialchars( $user->display_name )?></option>
                <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="input_email"><?php echo bkntcsaas__('Email')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_email" placeholder="example@gmail.com" value="<?php echo htmlspecialchars($parameters['tenant']['email'])?>" maxlength="100">
    </div>
    <div class="form-group col-md-6" data-hide="create_password">
        <label for="input_wp_user_password"><?php echo bkntcsaas__('User password')?></label>
        <input type="text" class="form-control" id="input_wp_user_password" placeholder="*****">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_domain"><?php echo bkntcsaas__('Domain')?> <span class="required-star">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="basic-addon3"><?php echo Helper::getHostName()?>/</span>
            </div>
            <input type="text" class="form-control" id="input_domain" value="<?php echo htmlspecialchars($parameters['tenant']['domain'])?>" maxlength="50">
        </div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_full_name"><?php echo bkntcsaas__('Full Name')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_full_name" value="<?php echo htmlspecialchars($parameters['tenant']['full_name'])?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_plan_id"><?php echo bkntcsaas__('Plan')?> <span class="required-star">*</span></label>
        <select class="form-control" id="input_plan_id">
            <?php
            foreach ( $parameters['plans'] AS $plan )
            {
                ?>
                <option value="<?php echo (int)$plan['id']?>" <?php echo $plan['id'] == $parameters['tenant']['plan_id'] ? ' selected' : ''?>><?php echo htmlspecialchars($plan['name'])?></option>
                <?php
            }
            ?>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="input_expires_in"><?php echo bkntcsaas__('Expires on')?> <span class="required-star">*</span></label>
        <input type="text" class="form-control" id="input_expires_in" value="<?php echo Date::dateSQL($parameters['tenant']['expires_in'])?>">
    </div>
</div>