<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/whitelabel_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/whitelabel_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntcsaas__('Whitelabel settings')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="whitelabel_logo_input"><?php echo bkntcsaas__('Logo')?> ( 162px × 35px ):</label>
						<div class="whitelabel_logo_img_div"><img src="<?php echo \BookneticApp\Providers\Helpers\Helper::profileImage( Helper::getOption('whitelabel_logo', 'logo'), 'Base')?>" id="whitelabel_logo_img"></div>
						<input type="file" id="whitelabel_logo_input">
					</div>

					<div class="form-group col-md-6">
						<label for="whitelabel_logo_sm_input"><?php echo bkntcsaas__('Small logo')?> ( 14px × 18px ):</label>
						<div class="whitelabel_logo_sm_img_div"><img src="<?php echo \BookneticApp\Providers\Helpers\Helper::profileImage( Helper::getOption('whitelabel_logo_sm', 'logo-sm'), 'Base')?>" id="whitelabel_logo_sm_img"></div>
						<input type="file" id="whitelabel_logo_sm_input">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_backend_title"><?php echo bkntcsaas__('Title of Back-end')?>:</label>
						<input class="form-control" id="input_backend_title" value="<?php echo Helper::getOption('backend_title', 'Booknetic');?>">
					</div>
				</div>


				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_backend_slug"><?php echo bkntcsaas__('Slug of Back-end')?>:</label>
						<input class="form-control" id="input_backend_slug" value="<?php echo Helper::getOption('backend_slug', 'booknetic');?>">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_documentation_url"><?php echo bkntcsaas__('URL of documentation')?>:</label>
						<input class="form-control" id="input_documentation_url" value="<?php echo Helper::getOption('documentation_url', 'https://www.booknetic.com/documentation/');?>">
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_powered_by"><?php echo bkntcsaas__('Powered by')?>:</label>
						<input class="form-control" id="input_powered_by" value="<?php echo Helper::getOption('powered_by', 'Booknetic');?>">
					</div>
				</div>

			</form>

		</div>
	</div>
</div>