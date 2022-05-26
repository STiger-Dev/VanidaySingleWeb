<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\TabUI;

?>

<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Payments')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-billing-id="<?php echo (int)$parameters['billing']['id']?>"></script>

<div class="fs-modal-title">
	<div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
	<div class="title-text"><?php echo bkntcsaas__('Add Payment')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">
		<form id="addPaymentForm">
            <div class="nowrap overflow-auto">
                <ul class="nav nav-tabs nav-light" data-tab-group="payments_add">
                    <?php foreach ( TabUI::get( 'payments_add' )->getSubItems() as $tab ): ?>
                        <li class="nav-item"><a class="nav-link " data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="tab-content mt-5">
                <?php foreach ( TabUI::get( 'payments_add' )->getSubItems() as $tab ): ?>
                    <div class="tab-pane " data-tab-content="payments_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent( $parameters ); ?></div>
                <?php endforeach; ?>
            </div>
		</form>
	</div>
</div>

<div class="fs-modal-footer">
	<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntcsaas__('CANCEL')?></button>
	<button type="button" class="btn btn-lg btn-primary" id="addPaymentSave"><?php echo $parameters['id']?bkntcsaas__('SAVE PAYMENT'):bkntcsaas__('ADD PAYMENT')?></button>
</div>
