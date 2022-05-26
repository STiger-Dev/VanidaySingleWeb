<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\UI\TabUI;

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Plans')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Plans')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-plan-id="<?php echo (int)$parameters['plan']['id']?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo bkntcsaas__('Add Plan')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="addPlanForm">
            <div class="nowrap overflow-auto">
                <ul class="nav nav-tabs nav-light" data-tab-group="plans_add">
                    <?php foreach ( TabUI::get( 'plans_add' )->getSubItems() as $tab ): ?>
                        <li class="nav-item"><a class="nav-link " data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="tab-content mt-5">
                <?php foreach ( TabUI::get( 'plans_add' )->getSubItems() as $tab ): ?>
                    <div class="tab-pane " data-tab-content="plans_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent( $parameters ); ?></div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntcsaas__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="addPlanSave"><?php echo $parameters['id']?bkntcsaas__('SAVE PLAN'):bkntcsaas__('ADD PLAN')?></button>
</div>

<div class="fs-popover" id="plan_color_panel">
    <div class="fs-popover-title">
        <span><?php echo bkntcsaas__('Select colors')?></span>
        <img src="<?php echo Helper::icon('cross.svg')?>" class="close-popover-btn">
    </div>
    <div class="fs-popover-content">
        <div class="fs-plan-colors-line">
            <div class="color-rounded color-r-1<?php echo ( $parameters['plan']['color'] == '#53d56c' ? ' selected-color' : '')?>" data-color="#53d56c"></div>
            <div class="color-rounded color-r-2<?php echo ( $parameters['plan']['color'] == '#26c0d6' ? ' selected-color' : '')?>" data-color="#26c0d6"></div>
            <div class="color-rounded color-r-3<?php echo ( $parameters['plan']['color'] == '#fd9b78' ? ' selected-color' : '')?>" data-color="#fd9b78"></div>
            <div class="color-rounded color-r-4<?php echo ( $parameters['plan']['color'] == '#cc65aa' ? ' selected-color' : '')?>" data-color="#cc65aa"></div>
            <div class="color-rounded color-r-5<?php echo ( $parameters['plan']['color'] == '#2078fa' ? ' selected-color' : '')?>" data-color="#2078fa"></div>
        </div>
        <div class="fs-plan-colors-line mt-3">
            <div class="color-rounded color-r-6<?php echo ( $parameters['plan']['color'] == '#947bbf' ? ' selected-color' : '')?>" data-color="#947bbf"></div>
            <div class="color-rounded color-r-7<?php echo ( $parameters['plan']['color'] == '#c9c2b8' ? ' selected-color' : '')?>" data-color="#c9c2b8"></div>
            <div class="color-rounded color-r-8<?php echo ( $parameters['plan']['color'] == '#527dde' ? ' selected-color' : '')?>" data-color="#527dde"></div>
            <div class="color-rounded color-r-9<?php echo ( $parameters['plan']['color'] == '#425a64' ? ' selected-color' : '')?>" data-color="#425a64"></div>
            <div class="color-rounded color-r-10<?php echo ( $parameters['plan']['color'] == '#ffbb44' ? ' selected-color' : '')?>" data-color="#ffbb44"></div>
        </div>

        <div class="form-row mt-3">
            <div class="form-group col-md-12">
                <label for="input_color_hex"><?php echo bkntcsaas__('Hex')?></label>
                <input type="text" class="form-control" id="input_color_hex" value="#53d56c">
            </div>
        </div>

        <div class="fs-popover-footer">
            <button type="button" class="btn btn-default btn-lg close-btn1"><?php echo bkntcsaas__('CLOSE')?></button>
            <button type="button" class="btn btn-primary btn-lg ml-2 save-btn1"><?php echo bkntcsaas__('SAVE')?></button>
        </div>

    </div>
</div>