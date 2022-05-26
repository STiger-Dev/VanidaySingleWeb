<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Integrations\PaymentGateways\Paypal;
use BookneticSaaS\Integrations\PaymentGateways\Stripe;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

$gateways = [
	'stripe'		=>	[
		'title'			=>	bkntcsaas__('Stripe'),
		'is_enabled'	=>	Helper::getOption('stripe_enable', 'on') == 'on'
	],
	'paypal'		=>	[
		'title'			=>	bkntcsaas__('Paypal'),
		'is_enabled'	=>	Helper::getOption('paypal_enable', 'on') == 'on'
	],
	'woocommerce'	=>	[
		'title'			=>	bkntcsaas__('WooCommerce'),
		'is_enabled'	=>	Helper::getOption('woocommerce_enable', 'on') == 'on'
	]
];
$gateways_order = Helper::getOption('payment_gateways_order', 'stripe,paypal,woocommerce');
$gateways_order = explode(',', $gateways_order);

foreach ( $gateways as $k => $gateway ) {
    if ( ! in_array( $k, $gateways_order ) )
    {
        $gateways_order[] = $k;
    }
}

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/payment_gateways_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/payment_gateways_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('Payments')?>
			<span class="ms-subtitle"><?php echo bkntcsaas__('Payment methods')?></span>
		</div>
		<div class="ms-content">

			<div class="step_settings_container">
				<div class="step_elements_list">
					<?php foreach ( $gateways_order AS $gateway ) {
						if ( empty( $gateways[ $gateway ] ) ) continue; ?>
						<div class="step_element" data-step-id="<?php echo $gateway; ?>">
							<span class="drag_drop_helper"><img src="<?php echo Helper::icon( 'drag-default.svg' ); ?>"></span>
							<span><?php echo $gateways[ $gateway ][ 'title' ]; ?></span>
							<div class="step_switch">
								<div class="fs_onoffswitch">
									<input type="checkbox" name="enable_gateway_<?php echo $gateway; ?>" class="fs_onoffswitch-checkbox green_switch" id="enable_gateway_<?php echo $gateway; ?>" <?php echo $gateways[ $gateway ][ 'is_enabled' ] ? 'checked' : ''; ?> data-gateway="<?php echo $gateway; ?>">
									<label class="fs_onoffswitch-label" for="enable_gateway_<?php echo $gateway; ?>"></label>
								</div>
							</div>
						</div>
                    <?php } ?>
				</div>
				<div class="step_elements_options dashed-border">
					<form id="booking_panel_settings_per_step" class="position-relative">

						<div class="hidden" data-step="paypal">

							<div class="form-group col-md-12">
								<label for="input_paypal_webhook_url"><?php echo bkntcsaas__('Webhook URI')?>:</label>
								<input class="form-control" id="input_paypal_webhook_url" value="<?php echo Paypal::webhookUrl()?>" readonly="">
							</div>

							<div class="form-group col-md-12">
								<label for="input_paypal_mode"><?php echo bkntcsaas__('Mode')?>:</label>
								<select class="form-control" id="input_paypal_mode">
									<option value="sandbox" <?php echo Helper::getOption('paypal_mode', 'sandbox')=='sandbox'?'selected':''?>><?php echo bkntcsaas__('Sandbox')?></option>
									<option value="live" <?php echo Helper::getOption('paypal_mode', 'sandbox')=='live'?'selected':''?>><?php echo bkntcsaas__('Live')?></option>
								</select>
							</div>

							<div class="form-group col-md-12">
								<label for="input_paypal_client_id"><?php echo bkntcsaas__('Client ID')?>:</label>
								<input class="form-control" id="input_paypal_client_id" value="<?php echo htmlspecialchars( Helper::getOption('paypal_client_id', '', null, true) )?>">
							</div>

							<div class="form-group col-md-12">
								<label for="input_paypal_client_secret"><?php echo bkntcsaas__('Client Secret')?>:</label>
								<input class="form-control" id="input_paypal_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('paypal_client_secret', '', null, true) )?>">
							</div>

							<div class="form-group col-md-12">
								<label for="input_paypal_webhook_id"><?php echo bkntcsaas__('Webhook ID')?>:</label>
								<input class="form-control" id="input_paypal_webhook_id" value="<?php echo htmlspecialchars( Helper::getOption('paypal_webhook_id', '', null, true) )?>">
							</div>

						</div>

						<div class="hidden" data-step="stripe">

							<div class="form-group col-md-12">
								<label for="input_stripe_webhook_url"><?php echo bkntcsaas__('Webhook URI')?>:</label>
								<input class="form-control" id="input_stripe_webhook_url" value="<?php echo Stripe::webhookUrl()?>" readonly="">
							</div>

							<div class="form-group col-md-12">
								<label for="input_stripe_client_id"><?php echo bkntcsaas__('Publishable key')?>:</label>
								<input class="form-control" id="input_stripe_client_id" value="<?php echo htmlspecialchars( Helper::getOption('stripe_client_id', '', null, true) )?>">
							</div>

							<div class="form-group col-md-12">
								<label for="input_stripe_client_secret"><?php echo bkntcsaas__('Secret key')?>:</label>
								<input class="form-control" id="input_stripe_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('stripe_client_secret', '', null, true) )?>">
							</div>

							<div class="form-group col-md-12">
								<label for="input_stripe_webhook_secret"><?php echo bkntcsaas__('Webhook Signing secret')?>:</label>
								<input class="form-control" id="input_stripe_webhook_secret" value="<?php echo htmlspecialchars( Helper::getOption('stripe_webhook_secret', '', null, true) )?>">
							</div>

						</div>

						<div class="hidden" data-step="woocommerce">

							<div class="form-group col-md-12">
								<label for="input_woocommerce_tenant_rediret_to"><?php echo bkntc__('Redirect tenant to')?>:</label>
								<select class="form-control" id="input_woocommerce_tenant_rediret_to">
									<option value="cart" <?php echo Helper::getOption('woocommerce_tenant_rediret_to', 'cart')=='cart'?'selected':''?>><?php echo bkntc__('Cart page')?></option>
									<option value="checkout" <?php echo Helper::getOption('woocommerce_tenant_rediret_to', 'cart')=='checkout'?'selected':''?>><?php echo bkntc__('Checkout page')?></option>
								</select>
							</div>

							<div class="form-group col-md-12">
								<label for="input_woocommerce_tenant_order_statuses"><?php echo bkntc__('WooCommerce order statuses for successfull payment')?>:</label>
								<select class="form-control" id="input_woocommerce_tenant_order_statuses" multiple="multiple">
									<?php if ( function_exists( 'wc_get_order_statuses' ) ): foreach ( wc_get_order_statuses() as $statusKey => $statusName ): ?>
										<option value="<?php echo htmlspecialchars($statusKey)?>"<?php echo in_array( $statusKey, explode( ',', Helper::getOption( 'woocommerce_tenant_order_statuses', 'wc-processing,wc-on-hold,wc-completed' ) ) ) ? ' selected' : ''?>><?php echo htmlspecialchars($statusName)?></option>
									<?php endforeach; endif; ?>
								</select>
							</div>

						</div>

					</form>
				</div>
			</div>

		</div>
	</div>
</div>