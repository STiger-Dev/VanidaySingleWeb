<?php

namespace BookneticSaaS\Backend\Customfields\Helpers;

use BookneticSaaS\Models\TenantFormInputChoice;
use BookneticSaaS\Providers\Helpers\Helper;

class FormElements
{

	public static function formElement( $preId, $type, $label, $isRequired = false, $helptext = '', $value = '', $inputId = '', $options = '', $file_name ='' )
	{
		$optionsArr = json_decode($options, true);
		$placeholder = isset( $optionsArr['placeholder'] ) && is_string( $optionsArr['placeholder'] ) ? $optionsArr['placeholder'] : '';

		if ( $type == 'label' )
		{
			return self::formLabel( $label, $helptext );
		}
		else if( $type == 'text' )
		{
			return self::formText( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'textarea' )
		{
			return self::formTextarea( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'number' )
		{
			return self::formNumber( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'date' )
		{
			return self::formDate( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'time' )
		{
			return self::formTime( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'select' )
		{
			return self::formSelect( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'checkbox' )
		{
			return self::formCheckbox( $preId, $label, $isRequired, $helptext, $inputId, $value );
		}
		else if( $type == 'radio' )
		{
			return self::formRadio( $preId, $label, $isRequired, $helptext, $inputId, $value );
		}
		else if( $type == 'file' )
		{
			return self::formFile( $label, $isRequired, $helptext, $placeholder, $value, $inputId, $file_name );
		}
		else if( $type == 'link' )
		{
			return self::formLink( $label, $helptext, $options );
		}
	}

	public static function formLabel( $label = '', $helptext = '' )
	{
		return '
			<div class="form-group col-md-12">
				<div class="form-control-plaintext" data-label="true">' . htmlspecialchars($label) . '</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formText( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
	{
		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
					. htmlspecialchars($label)
					. ( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .	
				'</label>
				<input placeholder="' . htmlspecialchars($placeholder) . '" type="text" class="form-control" data-input-id="' . (int)$inputId .'" value="' . htmlspecialchars($value) . '">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formTextarea( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
	{
		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
					. htmlspecialchars($label) . 
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				'</label>
				<textarea placeholder="' . htmlspecialchars($placeholder) . '" class="form-control" data-input-id="' . (int)$inputId .'">' . htmlspecialchars($value) . '</textarea>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formNumber( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
	{
		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
					. htmlspecialchars($label) . 
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				'</label>
				<input placeholder="' . htmlspecialchars($placeholder) . '" type="number" class="form-control" value="' . htmlspecialchars($value) . '" data-input-id="' . (int)$inputId .'">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formDate( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
	{
		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
					. htmlspecialchars($label) .
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				'</label>
				<input placeholder="' . htmlspecialchars($placeholder) . '" type="text" class="form-control form-control-date-input" value="' . htmlspecialchars($value) . '" data-input-id="' . (int)$inputId .'">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formTime( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
	{
		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
					. htmlspecialchars($label) .  
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				'</label> 
				<input placeholder="' . htmlspecialchars($placeholder) . '" type="time" class="form-control" value="' . htmlspecialchars($value) . '" data-input-id="' . (int)$inputId .'">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formSelect( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = null, $inputId = '' )
	{
		$getOptionValue = empty($value) ? false : TenantFormInputChoice::where('id', (int)$value)->fetch();
		$getAllOptions  = TenantFormInputChoice::where('form_input_id', (int)$inputId)->fetchAll();

		$options_html = '<option value="">...</option>';
		
		foreach( $getAllOptions as $option )
		{
			$options_html .= ('<option value="' . htmlspecialchars($option->id) .  '"' . ($option->id == $value ? ' selected="true"' : '') . '>' . htmlspecialchars($option->title) . '</option>');
		}


		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
					. htmlspecialchars($label) .  
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				'</label>
				<select class="form-control custom-input-select2" data-placeholder="' . htmlspecialchars( $placeholder ) . '" data-input-id="' . (int)$inputId .'">
					' . $options_html . '
				</select>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formCheckbox( $preId, $label = '', $isRequired = false, $helptext = '', $inputId = '', $value = null )
	{
		$value = explode(',', $value);
		$choicesHTML = '';

		if( $inputId == -1 )
		{
			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_checkbox_' . $preId . '_1" type="checkbox" data-input-id="' . (int)$inputId .'" value="1">
						<label for="custom_checkbox_'.$preId.'_1"> '.bkntcsaas__('Choice 1').'</label>
					</div>';

			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_checkbox_' . $preId . '_2" type="checkbox" data-input-id="' . (int)$inputId .'" value="2">
						<label for="custom_checkbox_'.$preId.'_2"> '.bkntcsaas__('Choice 2').'</label>
					</div>';
		}
		else if( !empty( $inputId ) )
		{
			$getChoices = TenantFormInputChoice::where('form_input_id', $inputId)->orderBy('order_number')->fetchAll();

			foreach ( $getChoices AS $choice )
			{
				$isChecked = in_array( $choice['id'], $value ) ? ' checked' : '';

				$choicesHTML .= '
					<div>
						<input id="custom_checkbox_' . $preId . '_' . (int)$choice['id'] . '" type="checkbox" data-input-id="' . (int)$inputId .'" value="' . (int)$choice['id'] . '"' . $isChecked . '>
						<label for="custom_checkbox_' . $preId . '_' . (int)$choice['id'] . '"> ' . htmlspecialchars( $choice['title'] ) . '</label>
					</div>';
			}
		}

		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
				
					. htmlspecialchars($label) .  
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				
				'</label>
				<div>
					' . $choicesHTML . '
				</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formRadio( $preId, $label = '', $isRequired = false, $helptext = '', $inputId = '', $value = null )
	{
		$value = explode(',', $value);
		$choicesHTML = '';

		if( $inputId == -1 )
		{
			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_radio_' . $preId . '_1" type="radio" name="custom_field_'.$preId.'_1" data-input-id="' . (int)$inputId .'" value="1">
						<label for="custom_radio_'.$preId.'_1"> '.bkntcsaas__('Choice 1').'</label>
					</div>';

			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_radio_' . $preId . '_2" type="radio" name="custom_field_'.$preId.'_2" data-input-id="' . (int)$inputId .'" value="2">
						<label for="custom_radio_'.$preId.'_2"> '.bkntcsaas__('Choice 2').'</label>
					</div>';
		}
		else if( !empty( $inputId ) )
		{
			$getChoices = TenantFormInputChoice::where('form_input_id', $inputId)->orderBy('order_number')->fetchAll();

			foreach ( $getChoices AS $choice )
			{
				$isChecked = in_array( $choice['id'], $value ) ? ' checked' : '';

				$choicesHTML .= '
					<div>
						<input id="custom_radio_'.$preId.'_' . (int)$choice['id'] . '" type="radio" name="custom_field_'.$preId.'_' . (int)$inputId . '" data-input-id="' . (int)$inputId .'" value="' . (int)$choice['id'] . '"' . $isChecked . '>
						<label for="custom_radio_'.$preId.'_' . (int)$choice['id'] . '"> ' . htmlspecialchars( $choice['title'] ) . '</label>
					</div>';
			}
		}

		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
				
					. htmlspecialchars($label) .  
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				
				'</label>
				<div>
					' . $choicesHTML . '
				</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formFile( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '', $file_name = '' )
	{
		return '
			<div class="form-group col-md-12">
				<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' 
				
					. htmlspecialchars($label) .  
					( $isRequired ? ' <span class="bookneticsaas_required_star required-star">*</span>' : '' ) .
				
				'</label>
				<input placeholder="' . htmlspecialchars($placeholder) . '" type="file" class="form-control" data-input-id="' . (int)$inputId .'">
				<div class="form-control" data-label="'.bkntcsaas__('BROWSE').'">' . (empty( $file_name ) ? htmlspecialchars($placeholder) : '<img src="' . Helper::assets('icons/unsuccess.svg') . '" class="remove_custom_file_btn" data-placeholder="' . htmlspecialchars($placeholder) . '" data-save-custom-data="' . (int)$inputId . '"> <a href="' . Helper::uploadedFileURL( $value, 'TenantCustomForms' ) . '" target="_blank">'.htmlspecialchars($file_name) . '</a>' ) . '</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

	public static function formLink( $label = '', $helptext = '', $options = '' )
	{
		$options = json_decode($options, true);

		$url = isset( $options['url'] ) && is_string($options['url']) ? $options['url'] : '#';

		return '
			<div class="form-group col-md-12">
				<div class="form-control-plain">
					<a href="' . $url . '" target="_blank">' . htmlspecialchars($label) . '</a>
				</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>
			</div>';
	}

}
