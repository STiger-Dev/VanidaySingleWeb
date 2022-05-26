<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;

?>

<div class="bookneticsaas_forgot_password" data-token="<?php echo htmlspecialchars($parameters['activation_token'])?>">
	<div class="bookneticsaas_step_1">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Reset password')?></div>
		<form method="post" class="bookneticsaas_form">
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_password1"><?php echo bkntcsaas__('New Password')?></label>
				<input type="password" id="bookneticsaas_password1">
			</div>
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_password2"><?php echo bkntcsaas__('Confirm Password')?></label>
				<input type="password" id="bookneticsaas_password2">
			</div>
			<div>
				<button type="button" class="bookneticsaas_btn_primary bookneticsaas_complete_forgot_password_btn"><?php echo bkntcsaas__('RESET PASSWORD')?></button>
			</div>
		</form>
	</div>
	<div class="bookneticsaas_step_2">
		<div class="bookneticsaas_forgot_password_completed">
			<img src="<?php echo Helper::assets('images/signup-success2.svg', 'front-end')?>" alt="">
		</div>
		<div class="bookneticsaas_forgot_password_completed_title"><?php echo bkntcsaas__('Congratulations!')?></div>
		<div class="bookneticsaas_forgot_password_completed_subtitle">
			<?php echo bkntcsaas__('Your password has been reset successfully !')?>
		</div>
		<div class="bookneticsaas_forgot_password_completed_footer">
			<a href="<?php echo get_permalink( Helper::getOption('sign_in_page') )?>" type="button" class="bookneticsaas_btn_primary bookneticsaas_goto_dashboard_btn"><?php echo bkntcsaas__('SIGN IN')?></a>
		</div>
	</div>
</div>
