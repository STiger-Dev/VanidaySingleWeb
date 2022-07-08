<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\StripePaymentGateway\bkntc__;
?>
<div class="form-group col-md-12">
    <label for="input_stripe_client_id"><?php echo bkntc__('Publishable key')?>:</label>
    <input class="form-control" id="input_stripe_client_id" value="<?php echo htmlspecialchars( Helper::getOption('stripe_client_id', '') )?>">
</div>

<div class="form-group col-md-12">
    <label for="input_stripe_client_secret"><?php echo bkntc__('Secret key')?>:</label>
    <input class="form-control" id="input_stripe_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('stripe_client_secret', '') )?>">
</div>
