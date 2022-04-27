<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

if( count( $parameters['customers'] ) === 0 || count( $parameters['extras'] ) === 0 )
{
    echo '<div class="text-secondary font-size-14 text-center">' . bkntc__('No extras found') . '</div>';
}
else
{
	foreach ( $parameters['customers'] AS $customerInf )
	{
		?>
		<div class="customer-fields-area dashed-border pb-3" data-customer-id="<?php echo (int)$customerInf['id']?>">

			<?php
			echo Helper::profileCard( $customerInf['first_name'].' '.$customerInf['last_name'] , $customerInf['profile_image'], $customerInf['email'], 'Customers' );

			foreach ( $parameters['extras'] AS $extraInf )
			{
				?>

				<div class="row mb-2" data-extra-id="<?php echo (int)$extraInf['id']?>">
					<div class="col-md-4">
						<div class="form-control-plaintext"><?php echo htmlspecialchars($extraInf['name'])?></div>
					</div>
					<div class="col-md-3">
						<input type="number" class="form-control extra_quantity" value="<?php echo isset($parameters['appointment_extras'][ (int)$customerInf['id'].'_'.(int)$extraInf['id'] ])?$parameters['appointment_extras'][ (int)$customerInf['id'].'_'.(int)$extraInf['id'] ]:0?>">
					</div>
					<div class="col-md-5">
						<div class="form-control-plaintext help-text text-secondary">( max quantity: <?php echo (int)$extraInf['max_quantity']?> )</div>
					</div>
				</div>

				<?php
			}
			?>

		</div>

		<?php
	}
}
