<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;

echo $parameters['table'];
?>

<script type="application/javascript" src="<?php echo Helper::assets('js/billing.js', 'Payments')?>"></script>
