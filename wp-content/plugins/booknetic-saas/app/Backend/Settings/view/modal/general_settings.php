<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

$all_pages = get_pages();
?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/general_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/general_settings.js', 'Settings')?>"></script>
	<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-select.min.css' )?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-select.min.js' )?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('General settings')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_sign_in_page"><?php echo bkntcsaas__('Sign-in page')?>:</label>
						<select class="form-control" id="input_sign_in_page">
							<?php foreach ( $all_pages AS $page ) : ?>
								<option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('sign_in_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group col-md-6">
						<label for="input_sign_up_page"><?php echo bkntcsaas__('Sign-Up page')?>:</label>
						<select class="form-control" id="input_sign_up_page">
							<?php foreach ( $all_pages AS $page ) : ?>
								<option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('sign_up_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_booking_page"><?php echo bkntcsaas__('Booking page')?>:</label>
						<select class="form-control" id="input_booking_page">
							<?php foreach ( $all_pages AS $page ) : ?>
								<option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('booking_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group col-md-6">
						<label for="input_forgot_password_page"><?php echo bkntcsaas__('Forgot Password page')?>:</label>
						<select class="form-control" id="input_forgot_password_page">
							<?php foreach ( $all_pages AS $page ) : ?>
								<option value="<?php echo htmlspecialchars($page->ID)?>"<?php echo Helper::getOption('forgot_password_page', '') == $page->ID ? ' selected' : ''?>><?php echo htmlspecialchars(empty($page->post_title) ? '-' : $page->post_title)?> (ID: <?php echo $page->ID?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_confirmation_number"><?php echo bkntcsaas__('Starting Appointment ID number')?>:</label>
						<input type="text" class="form-control" id="input_confirmation_number" value="<?php echo (int)$parameters['confirmation_number']?>">
					</div>
					<div class="form-group col-md-6">
						<label for="input_google_maps_api_key"><?php echo bkntcsaas__('Google Maps API Key')?>:</label>
						<input class="form-control" id="input_google_maps_api_key" value="<?php echo Helper::getOption('google_maps_api_key', '');?>">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label>&nbsp;</label>
						<div class="form-control-checkbox">
							<label for="input_google_recaptcha"><?php echo bkntcsaas__('Activate Google reCAPTCHA')?>:</label>
							<div class="fs_onoffswitch">
								<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_google_recaptcha"<?php echo Helper::getOption('google_recaptcha', 'off')=='on'?' checked':''?>>
								<label class="fs_onoffswitch-label" for="input_google_recaptcha"></label>
							</div>
						</div>
					</div>
					<div class="form-group col-md-3" data-hide-key="recaptcha">
						<label for="input_google_recaptcha_site_key"><?php echo bkntcsaas__('Site Key')?>:</label>
						<input type="text" class="form-control" id="input_google_recaptcha_site_key" value="<?php echo Helper::getOption('google_recaptcha_site_key', '')?>">
					</div>
					<div class="form-group col-md-3" data-hide-key="recaptcha">
						<label for="input_google_recaptcha_secret_key"><?php echo bkntcsaas__('Secret Key')?>:</label>
						<input type="text" class="form-control" id="input_google_recaptcha_secret_key" value="<?php echo Helper::getOption('google_recaptcha_secret_key', '')?>">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-3">
						<label for="input_trial_plan_id"><?php echo bkntcsaas__('Trial plan')?>: <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntcsaas__("When a new Tenant registers, the Tenant automatically has permissions according to the trial plan you choose here.")?>"></i></label>
						<select class="form-control" id="input_trial_plan_id">
							<?php foreach ( Plan::orderBy('order_by')->fetchAll() AS $plan ) : ?>
								<option value="<?php echo $plan->id?>"<?php echo ($plan->is_default ? ' selected' : '')?>><?php echo htmlspecialchars($plan->name)?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group col-md-3">
						<label for="input_trial_period"><?php echo bkntcsaas__('Trial period (days)')?>:</label>
						<input type="text" class="form-control" id="input_trial_period" value="<?php echo (int)Helper::getOption('trial_period', 30)?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_expire_plan_id"><?php echo bkntcsaas__('Plan for the expired tenants')?>: <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntcsaas__("If the plan that the tenant subscribed to is expired and the Tenant does not pay for the next cycle, the Tenant's permissions will automatically be limited according to the plan you choose here.")?>"></i></label>
						<select class="form-control" id="input_expire_plan_id">
							<?php foreach ( Plan::orderBy('order_by')->fetchAll() AS $plan ) : ?>
								<option value="<?php echo $plan->id?>"<?php echo ($plan->expire_plan ? ' selected' : '')?>><?php echo htmlspecialchars($plan->name)?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label>&nbsp;</label>
						<div class="form-control-checkbox">
							<label for="input_enable_language_switcher"><?php echo bkntcsaas__('Enable the Language switcher for tenants')?>:</label>
							<div class="fs_onoffswitch">
								<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_enable_language_switcher"<?php echo Helper::getOption('enable_language_switcher', 'off')=='on'?' checked':''?>>
								<label class="fs_onoffswitch-label" for="input_enable_language_switcher"></label>
							</div>
						</div>
					</div>
					<div class="form-group col-md-6" data-hide-key="enable_language_switcher">
						<label for="input_active_languages"><?php echo bkntcsaas__('Select languages')?>:</label>
						<?php
						echo str_replace( ['<select name=', 'value=""'], ['<select class="form-control languagepicker" data-value="'.htmlspecialchars(json_encode( Helper::getOption('active_languages', []) )).'" data-live-search="true" multiple name=', 'value="en_US"'], wp_dropdown_languages([
							'id'        =>  'input_active_languages',
							'echo'      =>  false,
							'selected'  =>  '-'
						]));
						?>
					</div>
				</div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <div class="form-control-checkbox">
                            <label for="input_new_wp_user_on_new_booking"><?php echo bkntc__('Create a new wordpress user on new booking')?>:</label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_new_wp_user_on_new_booking"<?php echo Helper::getOption('new_wp_user_on_new_booking', 'off')=='on'?' checked':''?>>
                                <label class="fs_onoffswitch-label" for="input_new_wp_user_on_new_booking"></label>
                            </div>
                        </div>
                    </div>
                </div>

			</form>

		</div>
	</div>
</div>