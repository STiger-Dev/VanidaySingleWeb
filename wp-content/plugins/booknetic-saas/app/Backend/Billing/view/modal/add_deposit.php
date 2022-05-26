<?php

defined( 'ABSPATH' ) or die();

use \BookneticSaaS\Providers\Helpers\Helper;
?>

<link rel="stylesheet" href="<?php echo \BookneticSaaS\Providers\Helpers\Helper::assets( 'css/add_deposit.css', 'Billing' )?>">
<script type="application/javascript" src="<?php echo \BookneticSaaS\Providers\Helpers\Helper::assets( 'js/add_deposit.js', 'Billing' )?>"></script>

<div class="fs-modal-title">
	<div class="title-icon badge-lg badge-purple"><i class="fas fa-coins"></i></div>
	<div class="title-text"><?php echo bkntcsaas__('Add deposit')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">

		<div class="mb-3 cursor-pointer btn btn-link" id="show_price_calculator"><?php echo bkntcsaas__('Price calculator')?> <i class="fa fa-angle-down"></i></div>

		<div class="hidden" id="price_calcualtor_section">
			<div class="form-row">
				<div class="form-group col-md-8">
					<label for="select_plan"><?php echo bkntcsaas__('Select plan')?></label>
					<select class="form-control" id="select_plan">
						<?php foreach ( $parameters['plans'] as $plan):?>
						<option value="<?php echo $plan->id?>" data-price-monthly="<?php echo Helper::floor( $plan->monthly_price )?>" data-price-annually="<?php echo Helper::floor( $plan->annually_price )?>"><?php echo htmlspecialchars( sprintf('%s ( %s / %s )', $plan->name, Helper::price($plan->monthly_price), Helper::price($plan->annually_price)) )?></option>
						<?php endforeach;?>
					</select>
				</div>
				<div class="form-group col-md-4">
					<label for="select_payment_cycle"><?php echo bkntcsaas__('Payment cycle')?></label>
					<select class="form-control" id="select_payment_cycle">
						<option value="monthly"><?php echo bkntcsaas__('Monthly')?></option>
						<option value="annually"><?php echo bkntcsaas__('Annually')?></option>
					</select>
				</div>
				<div class="form-group col-md-12 calcualte_btn_section">
					<button type="button" class="btn btn-info" id="calcualte_btn"><?php echo bkntcsaas__('Calculate')?></button>
				</div>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-12">
				<label for="input_add_deposit"><?php echo bkntcsaas__('Amount')?> ( <?php print \BookneticSaaS\Providers\Helpers\Helper::currencySymbol()?> )</label>
				<input type="text" id="input_add_deposit" class="form-control" placeholder="0">
			</div>
		</div>

	</div>
</div>

<div class="fs-modal-footer">
	<button type="button" class="btn btn-lg btn-success" id="modal_add_deposit_btn"><?php echo bkntcsaas__('ADD DEPOSIT')?></button>
	<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntcsaas__('CLOSE')?></button>
</div>