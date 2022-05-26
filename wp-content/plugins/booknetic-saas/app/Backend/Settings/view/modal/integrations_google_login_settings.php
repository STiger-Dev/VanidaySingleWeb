<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Integrations\LoginButtons\GoogleLogin;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/integrations_google_login_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/integrations_google_login_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('Google API / Continue with Google button')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row enable_disable_row">

					<div class="form-group col-md-2">
						<input id="input_google_login_enable" type="radio" name="input_google_login_enable" value="off"<?php echo Helper::getOption('google_login_enable', 'off')=='off'?' checked':''?>>
						<label for="input_google_login_enable"><?php echo bkntcsaas__('Disabled')?></label>
					</div>
					<div class="form-group col-md-2">
						<input id="input_google_login_disable" type="radio" name="input_google_login_enable" value="on"<?php echo Helper::getOption('google_login_enable', 'off')=='on'?' checked':''?>>
						<label for="input_google_login_disable"><?php echo bkntcsaas__('Enabled')?></label>
					</div>

				</div>

				<div id="integrations_google_login_settings_area">

					<div class="form-row">
						<div class="form-group col-md-12">
							<label for="input_google_calendar_redirect_uri"><?php echo bkntcsaas__('Redirect URI')?>:</label>
							<input class="form-control" id="input_google_calendar_redirect_uri" value="<?php echo GoogleLogin::callbackURL() ?>" readonly>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="input_google_login_app_id"><?php echo bkntcsaas__('App ID')?>: <span class="required-star">*</span></label>
							<input class="form-control" id="input_google_login_app_id" value="<?php echo htmlspecialchars( Helper::getOption('google_login_app_id', '') )?>">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="input_google_login_app_secret"><?php echo bkntcsaas__('App Secret')?>: <span class="required-star">*</span></label>
							<input class="form-control" id="input_google_login_app_secret" value="<?php echo htmlspecialchars( Helper::getOption('google_login_app_secret', '') )?>">
						</div>
					</div>



				</div>

			</form>

		</div>
	</div>
</div>