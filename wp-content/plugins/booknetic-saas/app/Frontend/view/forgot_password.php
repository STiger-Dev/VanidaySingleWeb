<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;

?>

<div class="bookneticsaas_forgot_password">
	<div class="bookneticsaas_step_1">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Forgot Password')?></div>
		<form method="post" class="bookneticsaas_form">
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_email"><?php echo bkntcsaas__('Email')?></label>
				<input type="text" id="bookneticsaas_email" maxlength="100" name="email">
			</div>
			<div>
				<button type="submit" class="bookneticsaas_btn_primary bookneticsaas_forgot_password_btn"><?php echo bkntcsaas__('CONTINUE')?></button>
			</div>
		</form>
		<div class="bookneticsaas_footer">
			<span><?php echo bkntcsaas__('Already have an account?')?></span>
			<a href="<?php echo get_permalink( Helper::getOption('sign_in_page') )?>"><?php echo bkntcsaas__('Sign in')?></a>
		</div>
	</div>
	<div class="bookneticsaas_step_2">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Congratulations!')?></div>
		<div class="bookneticsaas_check_your_email">
			<?php echo bkntcsaas__('We need to verify your email.')?><br/>
			<?php echo bkntcsaas__('Please, check your inbox for a confirmation link.')?>
		</div>
		<div class="bookneticsaas_email_success">
			<img src="<?php echo Helper::assets('images/signup-success.svg', 'front-end')?>" alt="">
		</div>
		<div class="bookneticsaas_footer bookneticsaas_resend_activation">
			<span><?php echo bkntcsaas__('Don’t receive the email?')?></span>
			<a href="javascript:;" class="bookneticsaas_resend_activation_link"><?php echo bkntcsaas__('Resend again')?></a>
		</div>
	</div>
</div>
