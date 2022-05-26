<?php

namespace BookneticSaaS\Backend\Customfields;

use BookneticSaaS\Models\TenantFormInput;
use BookneticSaaS\Models\TenantFormInputChoice;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function save_form()
	{
		$inputs	= Helper::_post('inputs', '', 'string');

		$inputs = json_decode($inputs, true);

		$saveIDs = [];
		$order = 1;
		foreach( $inputs AS $input )
		{
			if(
				!(
					is_array( $input )
					&& isset( $input['id'] ) && is_numeric($input['id']) && $input['id'] >= 0
					&& isset( $input['type'] ) && is_string($input['type']) && in_array( $input['type'], ['label', 'text', 'textarea', 'number', 'date', 'time', 'select', 'checkbox', 'radio', 'file', 'link'] )
				)
			)
			{
				continue;
			}

			$inputId		= (int)$input['id'];
			$inputType		= $input['type'];
			$label			= isset($input['label']) ? $input['label'] : '';
			$help_text		= isset($input['help_text']) ? $input['help_text'] : '';
			$is_required	= isset($input['is_required']) && $input['is_required'] ? 1 : 0;
			$choices		= isset($input['choices']) && is_array( $input['choices'] ) ? $input['choices'] : [];

			if( !in_array( $inputType, [ 'select', 'checkbox', 'radio' ] ) )
			{
				$choices = [];
			}

			if( mb_strlen( $label, 'utf-8' ) > 255 )
			{
				$label = mb_substr( $label, 0, 255, 'UTF-8' );
			}

			if( mb_strlen( $help_text, 'utf-8' ) > 500 )
			{
				$help_text = mb_substr( $help_text, 0, 500, 'UTF-8' );
			}

			$allowedOptions = [ 'placeholder', 'min_length', 'max_length', 'url', 'allowed_file_formats' ];

			foreach( $input AS $inputKey => $inputValue )
			{
				if( !in_array( $inputKey, $allowedOptions ) )
				{
					unset( $input[ $inputKey ] );
				}

				if( ($inputKey == 'placeholder' || $inputKey == 'url') && mb_strlen( $inputValue, 'utf-8' ) > 200 )
				{
					$input[ $inputKey ] = mb_substr($inputValue, 0, 200, 'UTF-8');
				}
			}

			$sqlData = [
				'label'			=>	$label,
				'help_text'		=>	$help_text,
				'is_required'	=>	$is_required,
				'order_number'	=>	$order,
				'options'		=>	json_encode( $input )
			];

			$isNewInput = $inputId > 0 ? false : true;
			if( $inputId > 0 )
			{
				TenantFormInput::where('id', $inputId)
						 ->where('type', $inputType)
						 ->update( $sqlData );
			}
			else
			{
				$sqlData['type']	= $inputType;

				TenantFormInput::insert( $sqlData );

				$inputId = DB::lastInsertedId();
			}

			$saveIDs[] = $inputId;

			$choiceOrder = 1;
			$saveChoiceIDs = [];
			foreach ( $choices AS $choice )
			{
				if(
					isset( $choice[0] ) && is_numeric( $choice[0] ) && $choice[0] >= 0
					&& isset( $choice[1] ) && is_string( $choice[1] )
				)
				{
					$choiceId = (int)$choice[0];
					$choiceTitle = (string)$choice[1];

					if( $choiceId > 0 )
					{
						TenantFormInputChoice::where('id', $choiceId)->where('form_input_id', $inputId)->update([

							'title'			=>	$choiceTitle,
							'order_number'	=>	$choiceOrder

						]);
					}
					else
					{
						TenantFormInputChoice::insert([
							'form_input_id'	=>	$inputId,
							'title'			=>	$choiceTitle,
							'order_number'	=>	$choiceOrder
						]);

						$choiceId = DB::lastInsertedId();
					}

					$saveChoiceIDs[] = $choiceId;

					$choiceOrder++;
				}
			}

			if( !$isNewInput )
			{
				$saveChoiceIDs = empty( $saveChoiceIDs ) ? '' : " AND id NOT IN ('" . implode( "', '", $saveChoiceIDs ) . "')";
				DB::DB()->query("DELETE FROM `" . DB::table('tenant_form_input_choices') . "` WHERE form_input_id='" . (int)$inputId . "' " . $saveChoiceIDs);
			}

			$order++;
		}

		$saveIDs = empty( $saveIDs ) ? '' : " WHERE id NOT IN ('" . implode( "', '", $saveIDs ) . "')";
		DB::DB()->query("DELETE FROM `" . DB::table('tenant_custom_data') . "` WHERE form_input_id IN (SELECT `id` FROM `" . DB::table('tenant_form_inputs') . "` " . $saveIDs . ")"); /*doit foreign key icaze vermir bu olmadan tenant_form_inputs-dan silmeye(tenant silende olan problem)*/
		DB::DB()->query("DELETE FROM `" . DB::table('tenant_form_input_choices') . "` WHERE form_input_id IN (SELECT `id` FROM `" . DB::table('tenant_form_inputs') . "` " . $saveIDs . ")");
		DB::DB()->query("DELETE FROM `" . DB::table('tenant_form_inputs') . "` " . $saveIDs);

		return $this->response( true );
	}

}
