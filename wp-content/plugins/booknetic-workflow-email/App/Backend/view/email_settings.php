<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\EmailWorkflow\EmailWorkflowAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\EmailWorkflow\bkntc__;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo EmailWorkflowAddon::loadAsset('assets/css/email_settings.css')?>">
	<script type="application/javascript" src="<?php echo EmailWorkflowAddon::loadAsset('assets/js/email_settings.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Email settings')?>
		</div>
		<div class="ms-content">

			<?php if( !Helper::isSaaSVersion() ):?>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_mail_gateway"><?php echo bkntc__('Mail Gateway')?>:</label>
					<select class="form-control" id="input_mail_gateway">
						<option value="wp_mail"<?php echo ( Helper::getOption('mail_gateway', 'wp_mail') == 'wp_mail' ? ' selected' : '' )?>><?php echo bkntc__('WordPress Mail')?></option>
						<option value="smtp"<?php echo ( Helper::getOption('mail_gateway', 'wp_mail') == 'smtp' ? ' selected' : '' )?>><?php echo bkntc__('SMTP')?></option>
					</select>
				</div>
			</div>

			<div class="smtp_details dashed-border">
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_hostname"><?php echo bkntc__('SMTP Hostname')?>:</label>
						<input class="form-control" id="input_smtp_hostname" value="<?php echo htmlspecialchars( Helper::getOption('smtp_hostname', '') )?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_port"><?php echo bkntc__('SMTP Port')?>:</label>
						<input class="form-control" id="input_smtp_port" value="<?php echo htmlspecialchars( Helper::getOption('smtp_port', '') )?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_secure"><?php echo bkntc__('SMTP Secure')?>:</label>
						<select class="form-control" id="input_smtp_secure">
							<option value="tls"<?php echo ( Helper::getOption('smtp_secure', 'tls') == 'tls' ? ' selected' : '' )?>><?php echo bkntc__('TLS')?></option>
							<option value="ssl"<?php echo ( Helper::getOption('smtp_secure', 'tls') == 'ssl' ? ' selected' : '' )?>><?php echo bkntc__('SSL')?></option>
							<option value="no"<?php echo ( Helper::getOption('smtp_secure', 'tls') == 'no' ? ' selected' : '' )?>><?php echo bkntc__('Disabled ( not recommend )')?></option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_username"><?php echo bkntc__('Username')?>:</label>
						<input class="form-control" id="input_smtp_username" value="<?php echo htmlspecialchars( Helper::getOption('smtp_username', '') )?>">
					</div>
					<div class="form-group col-md-6">
						<label for="input_smtp_password"><?php echo bkntc__('Password')?>:</label>
						<input class="form-control" id="input_smtp_password" value="<?php echo htmlspecialchars( Helper::getOption('smtp_password', '') )?>">
					</div>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_email"><?php echo bkntc__('Sender E-mail')?>:</label>
					<input class="form-control" id="input_sender_email" value="<?php echo htmlspecialchars( Helper::getOption('sender_email', '') )?>">
				</div>
				<div class="form-group col-md-6">
					<label for="input_sender_name"><?php echo bkntc__('Sender Name')?>:</label>
					<input class="form-control" id="input_sender_name" value="<?php echo htmlspecialchars( Helper::getOption('sender_name', '') )?>">
				</div>
			</div>
			<?php else:?>
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_sender_name"><?php echo bkntc__('Sender Name')?>:</label>
						<input class="form-control" id="input_sender_name" value="<?php echo htmlspecialchars( Helper::getOption('sender_name', '') )?>">
					</div>
				</div>
			<?php endif;?>

		</div>
	</div>
</div>