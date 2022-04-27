<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\UI\TabUI;

$gateways = PaymentGatewayService::getGateways( false, true );

?>

<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/payment_gateways_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/payment_gateways_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Payments')?>
			<span class="ms-subtitle"><?php echo bkntc__('Payment methods')?></span>
		</div>
		<div class="ms-content">

			<div class="step_settings_container">
				<div class="step_elements_list">

					<?php foreach( TabUI::get('payment_gateways_settings')->getSubItems() as $tabItem ): ?>
						<div class="step_element" data-step-id="<?php echo $tabItem->getSlug(); ?>">
							<span class="drag_drop_helper"><img src="<?php echo Helper::icon('drag-default.svg')?>"></span>
							<span><?php echo $tabItem->getTitle(); ?></span>
							<div class="step_switch">
								<div class="fs_onoffswitch">
									<input type="checkbox" data-slug="<?php echo $tabItem->getSlug(); ?>" name="enable_gateway_<?php echo $tabItem->getSlug(); ?>" class="bkntc_enable_payment_gateway fs_onoffswitch-checkbox green_switch" id="enable_gateway_<?php echo $tabItem->getSlug(); ?>" <?php echo ! empty( PaymentGatewayService::find( $tabItem->getSlug() ) ) && PaymentGatewayService::find( $tabItem->getSlug() )->isEnabled() ? 'checked' : ''; ?>  data-slug="<?php echo $tabItem->getSlug(); ?>">
									<label class="fs_onoffswitch-label" for="enable_gateway_<?php echo $tabItem->getSlug(); ?>"></label>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

				</div>

				<div class="step_elements_options dashed-border">
					<form id="booking_panel_settings_per_step" class="position-relative">
						<?php foreach( TabUI::get('payment_gateways_settings')->getSubItems() as $tabItem ): ?>
						<div class="hidden" data-step="<?php echo $tabItem->getSlug(); ?>">
							<?php echo $tabItem->getContent(); ?>
						</div>
						<?php endforeach; ?>
					</form>
				</div>
			</div>

		</div>
	</div>
</div>
