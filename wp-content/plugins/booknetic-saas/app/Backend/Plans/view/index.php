<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;

echo $parameters['table'];
?>

<script type="application/javascript" src="<?php echo Helper::assets('js/plans.js', 'Plans')?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/plan.css', 'Plans')?>" />

<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/bootstrap-colorpicker.min.css', 'Plans')?>" />
<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-colorpicker.min.js', 'Plans')?>"></script>

<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js', 'Plans')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css', 'Plans')?>" type="text/css">