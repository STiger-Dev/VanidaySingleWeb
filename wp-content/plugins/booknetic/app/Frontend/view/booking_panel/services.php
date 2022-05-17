<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

if( count( $parameters['services'] ) == 0 )
{
	echo '<div class="booknetic_empty_box"><img src="' . Helper::assets('images/empty-service.svg', 'front-end') . '"><span>' . bkntc__('Service not found. Please go back and select a different option.') . '</div>';
}
else
{
    echo '<div class="bkntc_service_list">';

$lastCategoryPrinted = null;
foreach ( $parameters['services'] AS $eq => $serviceInf )
{
	if( $lastCategoryPrinted != $serviceInf['category_id'] )
	{
		echo '<div class="booknetic_service_category booknetic_fade">' . htmlspecialchars($serviceInf['category_name']) . '</div>';
		$lastCategoryPrinted = $serviceInf['category_id'];
	}
	?>

    <div class="booknetic_service_card booknetic_fade" data-id="<?php echo $serviceInf[ 'id' ]; ?>" data-is-recurring="<?php echo (int) $serviceInf[ 'is_recurring' ]; ?>" data-has-extras="<?php echo $serviceInf[ 'extras_count' ] > 0 ? 'true':'false'; ?>">
        <div class="booknetic_service_card_header">
            <div class="booknetic_service_card_image">
                <img src="<?php echo Helper::profileImage( $serviceInf[ 'image' ], 'Services' ); ?>">
            </div>

            <div class="booknetic_service_card_title">
                <span><?php echo htmlspecialchars( $serviceInf[ 'name' ] ); ?></span>
                <span <?php echo $serviceInf[ 'hide_duration' ] == 1 ? 'class="booknetic_hidden"' : ''; ?>><?php echo Helper::secFormat( $serviceInf[ 'duration' ] * 60 ); ?></span>
            </div>

            <div class="booknetic_service_card_price <?php echo $serviceInf[ 'hide_price' ] == 1 ? 'booknetic_hidden' : ''; ?>">
                <?php echo Helper::price( $serviceInf[ 'real_price' ] == -1 ? $serviceInf[ 'price' ] : $serviceInf[ 'real_price' ] ); ?>
            </div>
        </div>

        <div class="booknetic_service_card_description">
            <?php echo htmlspecialchars( Helper::cutText( $serviceInf[ 'notes' ], 200 ) ); ?>
        </div>
    </div>

	<?php
}

do_action('bkntc_service_step_footer', $parameters['services']);

echo '</div>';
}
