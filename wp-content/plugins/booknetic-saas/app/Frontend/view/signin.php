<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;

?>

<div class="bookneticsaas_login">
	<div class="bookneticsaas_header"><?php echo bkntcsaas__('Sign In')?></div>
	<form class="bookneticsaas_form">
		<div class="bookneticsaas_form_element">
			<label for="bookneticsaas_email"><?php echo bkntcsaas__('Email')?></label>
			<input type="text" id="bookneticsaas_email" name="email">
		</div>
		<div class="bookneticsaas_form_element">
			<label for="bookneticsaas_password"><?php echo bkntcsaas__('Password')?></label>
			<input type="password" id="bookneticsaas_password" name="password">
		</div>
		<div class="bookneticsaas_form_element"><a href="<?php echo get_permalink( Helper::getOption('forgot_password_page') )?>" class="bookneticsaas_forgot_password"><img src="<?php echo Helper::icon('question.svg', 'front-end')?>" alt="?"><span><?php echo bkntcsaas__('Forgot password?')?></span></a></div>
		<div>
			<button type="submit" class="booknetic_btn_primary bookneticsaas_login_btn"><?php echo bkntcsaas__('SIGN IN')?></button>
		</div>
	</form>
	<div class="bookneticsaas_footer">
		<span><?php echo bkntcsaas__('Don\'t have an account?')?></span>
		<a href="<?php echo get_permalink( Helper::getOption('sign_up_page') )?>"><?php echo bkntcsaas__('Sign up')?></a>
	</div>
</div>
