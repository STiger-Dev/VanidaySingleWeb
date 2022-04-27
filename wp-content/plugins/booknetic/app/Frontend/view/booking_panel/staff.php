<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

if( count( $parameters['staff'] ) == 0 )
{
	echo '<div class="booknetic_empty_box"><img src="' . Helper::assets('images/empty-staff.svg', 'front-end') . '"><span>' . bkntc__('Staff not found. Please go back and select a different option.') . '</div>';
	return;
}
$footer_text_option = Helper::getOption('footer_text_staff', '1');

if( Helper::getOption('any_staff', 'off') == 'on' ) :
?>
	<div class="booknetic_card booknetic_fade" data-id="-1">
		<div class="booknetic_card_image">
			<img src="<?php echo Helper::icon('any_staff.svg', 'front-end')?>">
		</div>
		<div class="booknetic_card_title">
			<div><?php echo bkntc__('Any staff')?></div>
			<?php if( $footer_text_option != '4' ) :?>
				<div class="booknetic_card_description"><?php echo bkntc__('Select an available staff')?></div>
			<?php endif; ?>
		</div>
	</div>
<?php
endif;

foreach ( $parameters['staff'] AS $eq => $staffInf ) :
	?>
	<div class="booknetic_card booknetic_fade" data-id="<?php echo $staffInf['id']?>">
		<div class="booknetic_card_image">
			<img src="<?php echo Helper::profileImage($staffInf['profile_image'], 'Staff')?>">
		</div>
		<div class="booknetic_card_title">
			<div><?php echo htmlspecialchars($staffInf['name'])?></div>
			<div class="booknetic_card_description">

				<?php if( !empty($staffInf['profession']) ) : ?>
					<div class="booknetic_staff_profession"><?php echo htmlspecialchars($staffInf['profession'])?></div>
				<?php endif; ?>

				<?php if( $footer_text_option == '1' || $footer_text_option == '2' ) : ?>
					<div><?php echo htmlspecialchars($staffInf['email'])?></div>
				<?php endif; ?>

				<?php if( $footer_text_option == '1' || $footer_text_option == '3' ) : ?>
					<div><?php echo htmlspecialchars($staffInf['phone_number'])?></div>
				<?php endif; ?>

			</div>
		</div>
	</div>
	<?php
endforeach;