<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\UI\TabUI;

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Tenants')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Tenants')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-tenant-id="<?php echo (int)$parameters['tenant']['id']?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo ($parameters['id'] > 0 ? bkntcsaas__('Edit Tenant') : bkntcsaas__('Add Tenant')) ?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="addTenantForm">


            <ul class="nav nav-tabs nav-light" data-tab-group="tenants_add">
                <?php foreach ( TabUI::get( 'tenants_add' )->getSubItems() as $key => $tab ): ?>
                    <li class="nav-item"><a class="nav-link " data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content mt-5">
                <?php foreach ( TabUI::get( 'tenants_add' )->getSubItems() as $key => $tab ): ?>
                    <div class="tab-pane " data-tab-content="tenants_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent( $parameters ); ?></div>
                <?php endforeach; ?>
            </div>

        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntcsaas__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="addTenantSave"><?php echo $parameters['id']?bkntcsaas__('SAVE TENANT'):bkntcsaas__('ADD TENANT')?></button>
</div>
