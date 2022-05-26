<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;


$formInputTpls = [
	'label'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formLabel('%label%', '%helptext%'),
	'text'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formText('%label%', false, '%helptext%', '%placeholder%' ),
	'textarea'	=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formTextarea('%label%', false, '%helptext%', '%placeholder%' ),
	'number'	=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formNumber('%label%', false, '%helptext%', '%placeholder%' ),
	'date'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formDate('%label%', false, '%helptext%', '%placeholder%' ),
	'time'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formTime('%label%', false, '%helptext%', '%placeholder%' ),
	'select'	=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formSelect('%label%', false, '%helptext%', '%placeholder%' ),
	'checkbox'	=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formCheckbox(0, '%label%', false, '%helptext%', -1 ),
	'radio'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formRadio(0, '%label%', false, '%helptext%', -1 ),
	'file'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formFile('%label%', false, '%helptext%', '%placeholder%' ),
	'link'		=>	\BookneticSaaS\Backend\Customfields\Helpers\FormElements::formLink('%label%', '%helptext%' )
];
?>

<script src="<?php echo Helper::assets('js/edit.js', 'Customfields')?>" id="notifications-script"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/edit.css', 'Customfields')?>" type="text/css">

<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo bkntcsaas__('Tenant custom fields')?></div>
	<div class="m_head_actions float-right">
		<button type="button" class="btn btn-lg btn-success float-right ml-1" id="save-form-btn"><i class="fa fa-check pr-2"></i> <?php echo bkntcsaas__('SAVE FORM')?></button>
	</div>
</div>

<div class="fs_separator"></div>

<div class="row m-4">

	<div class="col-xl-3 col-md-6 col-lg-5 p-3 pr-md-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntcsaas__('Elements')?></div>
			<div class="fs_portlet_content p-0">

				<div class="row m-0 p-0">

					<div class="col-md-6 p-0 formbuilder_element" data-type="label">
						<img src="<?php echo Helper::icon('label.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Label')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="text">
						<img src="<?php echo Helper::icon('text.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Text input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="textarea">
						<img src="<?php echo Helper::icon('textarea.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Textarea')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="number">
						<img src="<?php echo Helper::icon('number.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Number input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="date">
						<img src="<?php echo Helper::icon('datepicker.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Date input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="time">
						<img src="<?php echo Helper::icon('timepicker.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Time input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="select">
						<img src="<?php echo Helper::icon('select.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Select')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="checkbox">
						<img src="<?php echo Helper::icon('checkbox.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Check-box')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="radio">
						<img src="<?php echo Helper::icon('radio.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Radio buttons')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="file">
						<img src="<?php echo Helper::icon('file.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('File')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="link">
						<img src="<?php echo Helper::icon('link.svg', 'Customfields')?>">
						<span><?php echo bkntcsaas__('Link')?></span>
					</div>

				</div>

			</div>
		</div>
	</div>

	<div class="col-xl-6 col-md-6 col-lg-7 p-3 pr-md-3 pr-xl-1 pl-md-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntcsaas__('Form')?></div>
			<div class="fs_portlet_content" id="formbuilder_area">
				<?php
				foreach ($parameters['inputs'] AS $input)
				{
					$type = $input['type'];

					if( !isset( $formInputTpls[ $type ] ) )
						continue;

					$tpl = $formInputTpls[ $type ];

					$options = json_decode( $input['options'], true );
					$options = is_array( $options ) ? $options : [];
					$options['label'] = $input['label'];
					$options['help_text'] = $input['help_text'];
					$options['is_required'] = $input['is_required'];

					if( isset( $input['choices'] ) )
					{
						$options['choices'] = $input['choices'];
					}

					$tpl = str_replace( [
						'%label%',
						'%helptext%',
						'%placeholder%',
						'data-required="false"',
					], [
						htmlspecialchars($input['label']),
						htmlspecialchars($input['help_text']),
						isset( $options['placeholder'] ) ? htmlspecialchars( $options['placeholder'] ) : '',
						'data-required="' . ($input['is_required'] ? 'true' : 'false') . '"',
					], $tpl );

					echo '<div class="form_element" data-type="' . htmlspecialchars($type) . '" data-id="' . (int)$input['id'] . '" data-options="' . htmlspecialchars( json_encode( $options ) ) . '">' . $tpl . '<img class="remove-element-btn" src="' . Helper::icon('remove.svg', 'Customfields') . '"></div>';

				}
				?>
			</div>
		</div>
	</div>

	<div class="col-xl-3 col-md-6 col-lg-5 p-3 pr-md-1 pr-xl-3 pl-xl-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntcsaas__('Options')?></div>
			<div id="formbuilder_options" class="fs_portlet_content">

				<div class="form-row hidden" data-for="label,text,textarea,number,date,time,select,checkbox,radio,file,link">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_label"><?php echo bkntcsaas__('Label')?></label>
						<input type="text" class="form-control" id="formbuilder_options_label" maxlength="255" placeholder="<?php echo bkntcsaas__('Max: 255 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="text,textarea,number,date,time,file,select">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_placeholder"><?php echo bkntcsaas__('Placeholder')?></label>
						<input type="text" class="form-control" id="formbuilder_options_placeholder" maxlength="200" placeholder="<?php echo bkntcsaas__('Max: 200 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="text,textarea,number">
					<div class="form-group col-md-6">
						<label for="formbuilder_options_min_length"><?php echo bkntcsaas__('Min length')?></label>
						<input type="text" class="form-control" id="formbuilder_options_min_length">
					</div>
					<div class="form-group col-md-6">
						<label for="formbuilder_options_max_length"><?php echo bkntcsaas__('Max length')?></label>
						<input type="text" class="form-control" id="formbuilder_options_max_length">
					</div>
				</div>

				<div class="form-row hidden" data-for="label,text,textarea,number,date,time,select,checkbox,radio,file,link">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_help_text"><?php echo bkntcsaas__('Help text')?></label>
						<input type="text" class="form-control" id="formbuilder_options_help_text" maxlength="500" placeholder="<?php echo bkntcsaas__('Max: 500 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="file">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_allowed_file_formats"><?php echo bkntcsaas__('Allowed file formats')?></label>
						<input type="text" class="form-control" id="formbuilder_options_allowed_file_formats" maxlength="500" placeholder="doc,docx,xls,xlsx,jpg,jpeg,png,gif,mp4,zip,rar,csv">
					</div>
				</div>

				<div class="form-row hidden" data-for="text,textarea,number,date,time,select,checkbox,radio,file">
					<div class="form-group col-md-12">
						<div class="form-control-plaintext">
							<input id="formbuilder_options_is_required" type="checkbox">
							<label for="formbuilder_options_is_required"><?php echo bkntcsaas__('Is required')?></label>
						</div>
					</div>
				</div>

				<div class="form-row hidden" data-for="link">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_url"><?php echo bkntcsaas__('URL')?></label>
						<input type="text" class="form-control" id="formbuilder_options_url" maxlength="200" placeholder="<?php echo bkntcsaas__('Max: 200 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="select,checkbox,radio" data-choices="true">
					<div class="form-group col-md-12">
						<div class="form-control-plaintext">
							<label><?php echo bkntcsaas__('Choices')?></label>
							<div id="choices_area"></div>
							<div id="formbuilder_options_add_new_choice"><i class="fa fa-plus-circle"></i> <?php echo bkntcsaas__('Add new')?></div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

</div>

<script>

	var formInputTpls			= <?php echo json_encode($formInputTpls)?>;
	var currentModuleAssetsURL	= "<?php echo Helper::assets('', 'Customfields')?>";

</script>