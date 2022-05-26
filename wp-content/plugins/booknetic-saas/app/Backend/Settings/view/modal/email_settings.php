<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php print Helper::assets('css/email_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php print Helper::assets('js/email_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php print bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php print bkntcsaas__('Email settings')?>
		</div>
		<div class="ms-content">

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_mail_gateway"><?php print bkntcsaas__('Mail Gateway')?>:</label>
					<select class="form-control" id="input_mail_gateway">
						<option value="wp_mail"<?php print ( Helper::getOption('mail_gateway', 'wp_mail') == 'wp_mail' ? ' selected' : '' )?>><?php print bkntcsaas__('WordPress Mail')?></option>
						<option value="smtp"<?php print ( Helper::getOption('mail_gateway', 'wp_mail') == 'smtp' ? ' selected' : '' )?>><?php print bkntcsaas__('SMTP')?></option>
					</select>
				</div>
			</div>

			<div class="smtp_details dashed-border">
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_hostname"><?php print bkntcsaas__('SMTP Hostname')?>:</label>
						<input class="form-control" id="input_smtp_hostname" value="<?php print htmlspecialchars( Helper::getOption('smtp_hostname', '', null, true) )?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_port"><?php print bkntcsaas__('SMTP Port')?>:</label>
						<input class="form-control" id="input_smtp_port" value="<?php print htmlspecialchars( Helper::getOption('smtp_port', '') )?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_secure"><?php print bkntcsaas__('SMTP Secure')?>:</label>
						<select class="form-control" id="input_smtp_secure">
							<option value="tls"<?php print ( Helper::getOption('smtp_secure', 'tls') == 'tls' ? ' selected' : '' )?>><?php print bkntcsaas__('TLS')?></option>
							<option value="ssl"<?php print ( Helper::getOption('smtp_secure', 'tls') == 'ssl' ? ' selected' : '' )?>><?php print bkntcsaas__('SSL')?></option>
							<option value="no"<?php print ( Helper::getOption('smtp_secure', 'tls') == 'no' ? ' selected' : '' )?>><?php print bkntcsaas__('Disabled ( not recommend )')?></option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_username"><?php print bkntcsaas__('Username')?>:</label>
						<input class="form-control" id="input_smtp_username" value="<?php print htmlspecialchars( Helper::getOption('smtp_username', '', null, true) )?>">
					</div>
					<div class="form-group col-md-6">
						<label for="input_smtp_password"><?php print bkntcsaas__('Password')?>:</label>
						<input class="form-control" id="input_smtp_password" value="<?php print htmlspecialchars( Helper::getOption('smtp_password', '', null, true) )?>">
					</div>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_email"><?php print bkntcsaas__('Sender E-mail')?>:</label>
					<input class="form-control" id="input_sender_email" value="<?php print htmlspecialchars( Helper::getOption('sender_email', '') )?>">
				</div>
				<div class="form-group col-md-6">
					<label for="input_sender_name"><?php print bkntcsaas__('Sender Name')?>:</label>
					<input class="form-control" id="input_sender_name" value="<?php print htmlspecialchars( Helper::getOption('sender_name', '') )?>">
				</div>
			</div>

		</div>
	</div>
</div>