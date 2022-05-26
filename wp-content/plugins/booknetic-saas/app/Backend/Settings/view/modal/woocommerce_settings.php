<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/woocommerce_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/woocommerce_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('Woocommerce settings')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row enable_disable_row">

					<div class="form-group col-md-2">
						<input id="input_allow_to_use_woocommerce_integration" type="radio" name="input_allow_to_use_woocommerce_integration" value="off"<?php echo Helper::getOption('allow_to_use_woocommerce_integration', 'off')=='off'?' checked':''?>>
						<label for="input_allow_to_use_woocommerce_integration"><?php echo bkntcsaas__('Disabled')?></label>
					</div>
					<div class="form-group col-md-2">
						<input id="input_customer_panel_disable" type="radio" name="input_allow_to_use_woocommerce_integration" value="on"<?php echo Helper::getOption('allow_to_use_woocommerce_integration', 'off')=='on'?' checked':''?>>
						<label for="input_customer_panel_disable"><?php echo bkntcsaas__('Enabled')?></label>
					</div>

				</div>

				<div id="woocommerce_settings_area">

					<div class="form-row">
						<div class="form-group col-md-6">
							<label>&nbsp;</label>
							<div class="form-control-checkbox">
								<label for="input_woocommerce_skip_confirm_step"><?php echo bkntcsaas__('Skip the Confirmation step')?>: <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntcsaas__("If you use Deposit Payments or Coupons, then you can have a confirmation step for your customers. Otherwise, it will redirect to WooCommerce by automatically skipping this step.")?>"></i></label>
								<div class="fs_onoffswitch">
									<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_woocommerce_skip_confirm_step"<?php echo Helper::getOption('woocommerce_skip_confirm_step', 'on')=='on'?' checked':''?>>
									<label class="fs_onoffswitch-label" for="input_woocommerce_skip_confirm_step"></label>
								</div>
							</div>
						</div>

						<div class="form-group col-md-6">
							<label for="input_woocommerce_rediret_to"><?php echo bkntcsaas__('Redirect customer to')?>:</label>
							<select class="form-control" id="input_woocommerce_rediret_to">
								<option value="cart" <?php echo Helper::getOption('woocommerce_rediret_to', 'cart')=='cart'?'selected':''?>><?php echo bkntcsaas__('Cart page')?></option>
								<option value="checkout" <?php echo Helper::getOption('woocommerce_rediret_to', 'cart')=='checkout'?'selected':''?>><?php echo bkntcsaas__('Checkout page')?></option>
							</select>
						</div>

						<div class="form-group col-md-12">
							<label for="input_woocommerde_order_details"><?php echo bkntcsaas__('Woocommerce order details')?>:</label>
							<textarea class="form-control" id="input_woocommerde_order_details"><?php echo htmlspecialchars( Helper::getOption('woocommerde_order_details', "Date: {appointment_date}\nTime: {appointment_start_time}\nStaff: {staff_name}") )?></textarea>
						</div>
					</div>

				</div>

			</form>

		</div>
	</div>
</div>