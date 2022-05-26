<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/payment_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/payment_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('Payments')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row">
					<div class="form-group col-md-3">
						<label for="input_currency"><?php echo bkntcsaas__('Currency')?>:</label>
						<select class="form-control" id="input_currency">
							<?php
							foreach ( $parameters['currencies'] AS $key => $currency )
							{
								echo '<option data-symbol="' . htmlspecialchars($currency['symbol']) . '" value="' . htmlspecialchars($key) . '"' . ( $key == Helper::getOption('currency', 'USD') ? ' selected' : '' ) . '>' . htmlspecialchars($currency['name'] . ' ( '. $currency['symbol'] . ' )') . '</option>';
							}
							?>
						</select>
					</div>
					<div class="form-group col-md-3">
						<label for="input_currency_symbol"><?php echo bkntcsaas__('Currency symbol')?>:</label>
						<input class="form-control" id="input_currency_symbol" value="<?php echo Helper::getOption('currency_symbol', Helper::currencySymbol())?>" maxlength="20">
					</div>
					<div class="form-group col-md-6">
						<label for="input_currency_format"><?php echo bkntcsaas__('Currency format')?>:</label>
						<select class="form-control" id="input_currency_format">
							<option value="1"<?php echo Helper::getOption('currency_format', '1')=='1' ? ' selected':''?>><?php echo $parameters['currency']?>100</option>
							<option value="2"<?php echo Helper::getOption('currency_format', '1')=='2' ? ' selected':''?>><?php echo $parameters['currency']?> 100</option>
							<option value="3"<?php echo Helper::getOption('currency_format', '1')=='3' ? ' selected':''?>>100<?php echo $parameters['currency']?></option>
							<option value="4"<?php echo Helper::getOption('currency_format', '1')=='4' ? ' selected':''?>>100 <?php echo $parameters['currency']?></option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-3">
						<label for="input_tenant_default_currency"><?php echo bkntcsaas__('Tenant default currency')?>:</label>
						<select class="form-control" id="input_tenant_default_currency">
							<?php
							foreach ( $parameters['currencies'] AS $key => $currency )
							{
								echo '<option data-symbol="' . htmlspecialchars($currency['symbol']) . '" value="' . htmlspecialchars($key) . '"' . ( $key == Helper::getOption('tenant_default_currency', 'USD') ? ' selected' : '' ) . '>' . htmlspecialchars($currency['name'] . ' ( '. $currency['symbol'] . ' )') . '</option>';
							}
							?>
						</select>
					</div>
					<div class="form-group col-md-3">
						<label for="input_tenant_default_currency_symbol"><?php echo bkntcsaas__('Tenant default currency symbol')?>:</label>
						<input class="form-control" id="input_tenant_default_currency_symbol" value="<?php echo Helper::getOption('tenant_default_currency_symbol', Helper::currencySymbol())?>" maxlength="20">
					</div>
					<div class="form-group col-md-6">
						<label for="input_tenant_default_currency_format"><?php echo bkntcsaas__('Tenant default currency format')?>:</label>
						<select class="form-control" id="input_tenant_default_currency_format">
							<option value="1"<?php echo Helper::getOption('tenant_default_currency_format', '1')=='1' ? ' selected':''?>><?php echo Helper::getOption('tenant_default_currency_symbol', Helper::currencySymbol())?>100</option>
							<option value="2"<?php echo Helper::getOption('tenant_default_currency_format', '1')=='2' ? ' selected':''?>><?php echo Helper::getOption('tenant_default_currency_symbol', Helper::currencySymbol())?> 100</option>
							<option value="3"<?php echo Helper::getOption('tenant_default_currency_format', '1')=='3' ? ' selected':''?>>100<?php echo Helper::getOption('tenant_default_currency_symbol', Helper::currencySymbol())?></option>
							<option value="4"<?php echo Helper::getOption('tenant_default_currency_format', '1')=='4' ? ' selected':''?>>100 <?php echo Helper::getOption('tenant_default_currency_symbol', Helper::currencySymbol())?></option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_price_number_format"><?php echo bkntcsaas__('Price number format')?>:</label>
						<select class="form-control" id="input_price_number_format">
							<option value="1"<?php echo Helper::getOption('price_number_format', '1')=='1' ? ' selected':''?>>45 000.00</option>
							<option value="2"<?php echo Helper::getOption('price_number_format', '1')=='2' ? ' selected':''?>>45,000.00</option>
							<option value="3"<?php echo Helper::getOption('price_number_format', '1')=='3' ? ' selected':''?>>45 000,00</option>
							<option value="4"<?php echo Helper::getOption('price_number_format', '1')=='4' ? ' selected':''?>>45.000,00</option>
						</select>
					</div>
					<div class="form-group col-md-6">
						<label for="input_price_number_of_decimals"><?php echo bkntcsaas__('Price number of decimals')?>:</label>
						<select class="form-control" id="input_price_number_of_decimals">
							<option value="0"<?php echo Helper::getOption('price_number_of_decimals', '2')=='0' ? ' selected':''?>>100</option>
							<option value="1"<?php echo Helper::getOption('price_number_of_decimals', '2')=='1' ? ' selected':''?>>100.0</option>
							<option value="2"<?php echo Helper::getOption('price_number_of_decimals', '2')=='2' ? ' selected':''?>>100.00</option>
							<option value="3"<?php echo Helper::getOption('price_number_of_decimals', '2')=='3' ? ' selected':''?>>100.000</option>
							<option value="4"<?php echo Helper::getOption('price_number_of_decimals', '2')=='4' ? ' selected':''?>>100.0000</option>
						</select>
					</div>
				</div>

			</form>

		</div>
	</div>
</div>