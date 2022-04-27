<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

echo $parameters['table'];
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-year-calendar.min.css')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-year-calendar.min.js')?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/staff.js', 'Staff')?>" id="staff-js12394610" data-edit="<?php echo $parameters['edit']?>"></script>
