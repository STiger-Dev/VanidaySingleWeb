<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

if( empty( $parameters['extras'] ) )
{
	echo '<div class="booknetic_empty_box"><img src="' . Helper::assets('images/empty-extras.svg', 'front-end') . '"><span>' . bkntc__('Extras not found in this service. You can select other service or click the <span class="booknetic_text_primary">"Next step"</span> button.' , [] , false) . '</span></div>';
}
else
{
    echo '<div class="bkntc_service_extras_list">';
	echo '<div class="booknetic_service_extra_title booknetic_fade">' . $parameters['service_name'] . '</div>';

	foreach ( $parameters['extras'] AS $eq => $extraInf )
	{
		?>
		<div class="booknetic_service_extra_card booknetic_fade<?php echo $extraInf['max_quantity'] == 1 ? ' booknetic_extra_on_off_mode' : ''?>" data-id="<?php echo (int)$extraInf['id']?>">
			<div class="booknetic_service_extra_card_image">
				<img src="<?php echo Helper::profileImage($extraInf['image'], 'Services')?>">
			</div>
			<div class="booknetic_service_extra_card_title">
				<span><?php echo htmlspecialchars($extraInf['name'])?></span>
				<span><?php echo $extraInf['duration'] && $extraInf['hide_duration'] != 1 ? Helper::secFormat($extraInf['duration']*60) : ''?></span>
			</div>
			<div class="booknetic_service_extra_card_price">
				<?php echo $extraInf['hide_price'] != 1 ? Helper::price( $extraInf['price'] ) : ''?>
			</div>
			<div class="booknetic_service_extra_quantity<?php echo $extraInf['max_quantity'] == 1 ? ' booknetic_hidden' : ''?>">
				<div class="booknetic_service_extra_quantity_dec">-</div>
				<input type="text" class="booknetic_service_extra_quantity_input" value="0" data-max-quantity="<?php echo (int)$extraInf['max_quantity']?>">
				<div class="booknetic_service_extra_quantity_inc">+</div>
			</div>
		</div>
		<?php
	}

    do_action('bkntc_service_extras_step_footer',
        array_map( function ( $extra ){
            return $extra->toArray();
        } , $parameters['extras']));

    echo '</div>';

}
