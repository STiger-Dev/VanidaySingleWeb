<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */

$saveCustomerId = 0;

if ( empty( $parameters['extras'] ) )
{
	echo '<div class="text-secondary font-size-14 text-center">' . bkntc__( 'No extras found' ) . '</div>';
}
else
{
	foreach ( $parameters['extras'] AS $customerInf )
	{

		echo '<div class="customer-fields-area dashed-border pb-3">';
		echo Helper::profileCard( $customerInf['name'] , $customerInf['profile_image'], $customerInf['email'], 'Customers' );

		echo '<div class="row text-primary"><div class="col-md-4">' . bkntc__('Extra name') . '</div><div class="col-md-3">' . bkntc__('Duration') . '</div><div class="col-md-3">' . bkntc__('Price') . '</div></div>';
		foreach ( $customerInf['extras'] AS $extra )
		{
			?>
			<div class="row mt-1">
				<div class="col-md-4">
					<div class="form-control-plaintext"><?php echo htmlspecialchars($extra['service_extras_name'])?><span class="btn btn-xs btn-light-warning ml-2">x<?php echo (int)$extra['quantity']?></span></div>
				</div>
				<div class="col-md-3">
					<div class="form-control-plaintext"><?php echo empty($extra['duration'])?'-':Helper::secFormat( $extra['duration'] * 60 )?></div>
				</div>
				<div class="col-md-3">
					<div class="form-control-plaintext"><?php echo Helper::price( $extra['price'] * $extra['quantity'] )?></div>
				</div>
			</div>
			<?php
		}

		echo '</div>';
	}
}
?>