<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

?>

<div id="booknetic_loading" class="hidden"><div><?php echo bkntcsaas__('Loading...')?></div><div><?php echo bkntcsaas__('( it can take some time, please wait... )')?></div></div>

<div id="booknetic_alert" class="hidden"></div>

<div class="booknetic-box-container">
	<div class="booknetic-box">
		<div class="booknetic-box-logo">
			<img src="<?php echo Helper::assets( 'images/logo-black.svg' ); ?>">
		</div>
		<div class="booknetic-box-info">
			<i class="fas fa-info-circle"></i><?php echo bkntcsaas__( 'Your plugin is disabled. Please activate the plugin.' ); ?>
		</div>
		<div class="booknetic-reason">
			<label><?php echo bkntcsaas__( 'Reason: %s', [ Helper::getOption( 'plugin_alert', '', false ) ], false ); ?></label>
		</div>
	</div>
</div>
