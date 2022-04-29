<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

?>

<div>
	<?php
	if(
		Helper::getOption('facebook_login_enable', 'off', false) == 'on'
		&& !empty( Helper::getOption('facebook_app_id', '', false) )
		&& !empty( Helper::getOption('facebook_app_secret', '', false) )
	)
	{
		?>
		<button type="button" class="booknetic_social_login_facebook" data-href="<?php echo site_url() . '/?' . Helper::getSlugName() . '_action=facebook_login'?>"><?php echo bkntc__('CONTINUE WITH FACEBOOK')?></button>
		<?php
	}

	if(
		Helper::getOption('google_login_enable', 'off', false) == 'on'
		&& !empty( Helper::getOption('google_login_app_id', '', false) )
		&& !empty( Helper::getOption('google_login_app_secret', '', false) )
	)
	{
		?>
		<button type="button" class="booknetic_social_login_google" data-href="<?php echo site_url() . '/?' . Helper::getSlugName() . '_action=google_login'?>"><?php echo bkntc__('CONTINUE WITH GOOGLE')?></button>
		<?php
	}
	?>
</div>

<div class="form-row">
	<div class="form-group col-md-<?php echo $parameters['show_only_name'] ? '12' : '6'?>">
		<label for="bkntc_input_name" data-required="true"><?php echo bkntc__('First name')?></label>
		<input type="text" id="bkntc_input_name" class="form-control" name="first_name" value="<?php echo htmlspecialchars($parameters['name'] . ( $parameters['show_only_name'] ? ($parameters['name'] ? ' ' : '') . $parameters['surname'] : '' ))?>">
	</div>
	<div class="form-group col-md-6<?php echo $parameters['show_only_name'] ? ' booknetic_hidden' : ''?>">
		<label for="bkntc_input_surname"<?php echo $parameters['show_only_name'] ? '' : ' data-required="true"'?>><?php echo bkntc__('Last name')?></label>
		<input type="text" id="bkntc_input_surname" class="form-control" name="last_name" value="<?php echo htmlspecialchars($parameters['show_only_name'] ? '' : $parameters['surname'])?>">
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-6">
		<label for="bkntc_input_email" <?php echo $parameters['email_is_required']=='on'?' data-required="true"':''?>><?php echo bkntc__('Email')?></label>
		<input type="text" id="bkntc_input_email" class="form-control" name="email" value="<?php echo htmlspecialchars($parameters['email'])?>" <?php echo !empty($parameters['email']) ? "disabled" : "" ?>>
	</div>
	<div class="form-group col-md-6">
		<label for="bkntc_input_phone" <?php echo $parameters['phone_is_required']=='on'?' data-required="true"':''?>><?php echo bkntc__('Phone')?></label>
		<input type="text" id="bkntc_input_phone" class="form-control" name="phone" value="<?php echo htmlspecialchars($parameters['phone'])?>" data-country-code="<?php echo Helper::getOption('default_phone_country_code', '')?>">
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-12">
		<label for="bkntc_input_company"><?php echo bkntc__('Company name(optional)')?></label>
		<input type="text" id="bkntc_input_company" placeholder="Company" class="form-control" name="company" value="<?php echo htmlspecialchars($parameters['company_name'])?>" <?php echo !empty($parameters['company_name']) ? "disabled" : "" ?>>
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-6">
		<label for="bkntc_input_vaccinate_confirm"><?php echo bkntc__('Kindly acknowledge and confirm you have been fully vaccinated prior to making this shave appointment.')?></label>
		<textarea id="bkntc_input_vaccinate_confirm" class="form-control" name="bkntc_input_vaccinate_confirm" value="<?php echo htmlspecialchars($parameters['vaccinate_confirm'])?>" <?php echo !empty($parameters['vaccinate_confirm']) ? "disabled" : "" ?>>
		</textarea>
	</div>
	<div class="form-group col-md-6 vaccinate_comment">
		<label for="bkntc_input_vaccinate_comment"><?php echo bkntc__('Comments(optional)')?></label>
		<textarea id="bkntc_input_vaccinate_comment" class="form-control" name="bkntc_input_vaccinate_comment" value="<?php echo htmlspecialchars($parameters['vaccinate_comment'])?>" <?php echo !empty($parameters['vaccinate_comment']) ? "disabled" : "" ?>>
		</textarea>
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-12">
		<div class="gray_border_box">
			<div class="item">
				<input type="checkbox" id="vaccinate_comment_agree_checkbox">
				<label for="vaccinate_comment_agree_checkbox">
					I confirm I meet the current COVID-19 vaccination requirements for this booking
				</label>
			</div>
			<div>
				Please be aware that we will require proof of your vaccination status on appointment arrival.
			</div>
		</div>
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-12">
		<div class="gray_border_box">
			<label for="bkntc_input_promo_code"><?php echo bkntc__('Have a promo code?')?></label>
			<input type="text" id="bkntc_input_promo_code" class="form-control" name="promo_code" value="<?php echo htmlspecialchars($parameters['promo_code'])?>" <?php echo !empty($parameters['company_name']) ? "disabled" : "" ?>>
		</div>
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-12">
		<div class="gray_border_box">
			<div class="item">
				<input type="checkbox" id="agree_privacy_policy">
				<label for="agree_privacy_policy">
					I agree to Sultans of Shave privacy policy
				</label>
			</div>
		</div>
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-12">
		<div class="gray_border_box">
			<div class="item">
				<input type="checkbox" id="subscribe_email">
				<label for="subscribe_email">
					I want to receive emails with the latest news and updates from Sultans of Shave  
				</label>
			</div>
		</div>
	</div>
</div>
<div class="form-row">
	<div class="form-group col-md-12">
		<div class="gray_border_box">
			<div>
				<p style="font-weight: bold;">
					Cancellation policy
				</p>
				No cancellations or changes allowed within 24 hours of the appointment.<br>
				In light of the current CoVid-19 situation, we are incorporating enhanced precautionary measures and will no longer accept appointments of customers who are:<br>
				1. Unvaccinated<br>
				2. Been in close contact with individuals who are on home quarantine, leave of absence or serving a stay at home notice.<br>
				<br>
				We seek your utmost cooperation and compliance to this protective measure, as we are committed to being socially responsible and keeping the community protected during this crucial period. Upon acknowledgement, you declare that you are not in violation of ANY of the above mentioned clauses, and agree to health and travel checks upon arrival in the outlet.<br>
				<br>
				Please treat your appointments with care as there are fellow gents that want to get in on the Sultan's experience!<br>
				Do call us in advance if you would like to make any changes to your appointment.<br>
			</div>
			<div class="item">
				<input type="checkbox" id="cancellation_agree_checkbox">
				<label for="cancellation_agree_checkbox">
					I agree
				</label>
			</div>
		</div>
	</div>
</div>
<?php if( $parameters['how_many_people_can_bring'] > 0 ) : ?>
    <div id="booknetic_bring_someone_section">
        <div class="form-row">
            <div class="form-group col-md-6">
                <input type="checkbox" id="booknetic_bring_someone_checkbox">
                <label for="booknetic_bring_someone_checkbox"><?php echo bkntc__('Bring People with You')?></label>
            </div>

            <div class="form-group col-md-6 booknetic_number_of_brought_customers d-none">
                <label for=""><?php echo bkntc__('Number of people:') ?></label>
                <div class="booknetic_number_of_brought_customers_quantity">
                    <div class="booknetic_number_of_brought_customers_dec">-</div>
                    <input type="text" class="booknetic_number_of_brought_customers_quantity_input" readonly value="0" data-max-quantity="<?php echo ($parameters['how_many_people_can_bring']);?>">
                    <div class="booknetic_number_of_brought_customers_inc">+</div>
                </div>
            </div>
        </div>
    </div>
<?php endif;?>

<?php do_action( 'bkntc_after_information_inputs', $parameters['service']); ?>
