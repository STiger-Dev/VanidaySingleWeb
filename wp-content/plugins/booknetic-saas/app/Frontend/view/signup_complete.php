<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;

?>

<div class="bookneticsaas_signup" data-token="<?php echo htmlspecialchars($parameters['activation_token'])?>">
	<div class="bookneticsaas_step_1">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Create URL')?></div>
		<div class="bookneticsaas_subtitle"><?php echo bkntcsaas__('Create Business URL address, what you’ll be use.')?></div>
		<form method="post" class="bookneticsaas_form">
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_domain"><?php echo bkntcsaas__('URL')?></label>
				<div class="bookneticsaas_input_group">
					<span><?php echo Helper::getHostName()?>/</span>
					<input type="text" id="bookneticsaas_domain" maxlength="50">
				</div>
			</div>
			<div>
				<button type="button" class="bookneticsaas_btn_primary bookneticsaas_continue_btn"><?php echo bkntcsaas__('CONTINUE')?></button>
			</div>
		</form>
		<div class="bookneticsaas_step_progress_bar">
			<div class="bookneticsaas_step_progress_bar_line" data-step="1"></div>
			<div class="bookneticsaas_step_progress_bar_txt">
				<span class="bookneticsaas_text_primary">01</span>
				<span>/</span>
				<span>02</span>
			</div>
		</div>
	</div>
	<div class="bookneticsaas_step_2">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Company Details')?></div>
		<div class="bookneticsaas_company_image">
			<div class="bookneticsaas_company_image_border"><img src="<?php echo Helper::assets('images/no-photo.png', 'front-end');?>"></div>
			<input type="file" id="bookneticsaas_company_image_input">
		</div>
		<form method="post" class="bookneticsaas_form">
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_company_name"><?php echo bkntcsaas__('Company name')?> <span class="bookneticsaas_required_star">*</span></label>
				<input type="text" id="bookneticsaas_company_name" placeholder="<?php echo bkntcsaas__('Enter company name')?>">
			</div>

			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_address"><?php echo bkntcsaas__('Address')?></label>
				<input type="text" id="bookneticsaas_address" placeholder="<?php echo bkntcsaas__('Enter address')?>">
			</div>

			<div class="bookneticsaas_form_element_row">
				<div class="bookneticsaas_form_element">
					<label for="bookneticsaas_phone_number"><?php echo bkntcsaas__('Phone number')?></label>
					<input type="text" id="bookneticsaas_phone_number" placeholder="<?php echo bkntcsaas__('Enter phone number')?>">
				</div>
				<div class="bookneticsaas_form_element">
					<label for="bookneticsaas_website"><?php echo bkntcsaas__('Website')?></label>
					<input type="text" id="bookneticsaas_website" placeholder="<?php echo bkntcsaas__('Enter website')?>">
				</div>
			</div>



			<div id="booknetic_tenant_custom_form" class="booknetic_appointment">
				
				<?php foreach ( $parameters['custom_fields'] AS $custom_data ): ?>

					<div class="">
						<?php
						echo \BookneticSaaS\Backend\Customfields\Helpers\FormElements::formElement( 1, $custom_data['type'], $custom_data['label'], $custom_data['is_required'], $custom_data['help_text'], '', $custom_data['id'], $custom_data['options'] );
						?>
					</div>

				<?php endforeach; ?>

			</div>



			<div>
				<button type="button" class="bookneticsaas_btn_primary bookneticsaas_complete_signup_btn"><?php echo bkntcsaas__('COMPLETE REGISTRATION')?></button>
			</div>
		</form>
		<div class="bookneticsaas_step_progress_bar">
			<div class="bookneticsaas_step_progress_bar_line" data-step="2"></div>
			<div class="bookneticsaas_step_progress_bar_txt">
				<span class="bookneticsaas_text_primary">02</span>
				<span>/</span>
				<span>02</span>
			</div>
		</div>
	</div>
	<div class="bookneticsaas_step_3">
		<div class="bookneticsaas_signup_completed">
			<img src="<?php echo Helper::assets('images/signup-success2.svg', 'front-end')?>" alt="">
		</div>
		<div class="bookneticsaas_signup_completed_title"><?php echo bkntcsaas__('Congratulations!')?></div>
		<div class="bookneticsaas_signup_completed_subtitle">
			<?php echo bkntcsaas__('You have successfully signed up. Head over to your Dashboard!')?>
		</div>
		<div class="bookneticsaas_signup_completed_footer">
			<a href="<?php echo admin_url( 'admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() )?>" type="button" class="bookneticsaas_btn_primary bookneticsaas_goto_dashboard_btn"><?php echo bkntcsaas__('GO TO DASHBOARD')?></a>
		</div>
	</div>
</div>
