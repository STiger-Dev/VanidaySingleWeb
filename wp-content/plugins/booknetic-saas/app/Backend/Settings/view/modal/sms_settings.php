<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/sms_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/sms_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('SMS/WhatsApp settings')?>
		</div>
		<div class="ms-content">

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sms_account_sid"><?php echo bkntcsaas__('Account SID')?>:</label>
					<input class="form-control" id="input_sms_account_sid" value="<?php echo htmlspecialchars( Helper::getOption('sms_account_sid', '') )?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sms_auth_token"><?php echo bkntcsaas__('Auth Token')?>:</label>
					<input class="form-control" id="input_sms_auth_token" value="<?php echo htmlspecialchars( Helper::getOption('sms_auth_token', '') )?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_phone_number"><?php echo bkntcsaas__('Sender phone number for SMS')?>:</label>
					<input class="form-control" id="input_sender_phone_number" value="<?php echo htmlspecialchars( Helper::getOption('sender_phone_number', '') )?>" placeholder="+15123456789">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_phone_number_whatsapp"><?php echo bkntcsaas__('Sender phone number for WhatsApp')?>:</label>
					<input class="form-control" id="input_sender_phone_number_whatsapp" value="<?php echo htmlspecialchars( Helper::getOption('sender_phone_number_whatsapp', '') )?>" placeholder="+15123456789">
				</div>
			</div>

		</div>
	</div>
</div>