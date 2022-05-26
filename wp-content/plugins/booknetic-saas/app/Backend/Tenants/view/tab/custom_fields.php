<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Backend\Customfields\Helpers\FormElements;

if ( empty( $parameters[ 'custom_fields' ] ) )
{
    echo '<div class="text-secondary font-size-14 text-center">' . bkntc__( 'No custom fields found' ) . '</div>';
}
else
{
    foreach ( $parameters['custom_fields'] AS $custom_data )
    {
        $form_element = FormElements::formElement( 1, $custom_data['type'], $custom_data['label'], $custom_data['is_required'], $custom_data['help_text'], $custom_data['value'], $custom_data['form_input_id'], $custom_data['options'], $custom_data['input_file_name']);

        echo '<div class="">' . $form_element . '</div>';
    }
}