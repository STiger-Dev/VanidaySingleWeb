<?php

defined( 'ABSPATH' ) or die();

?>

<?php foreach ( $parameters['capabilities_list'] as $capabilitySlug => $capabilityInf ): ?>
    <div class="form-group">
        <div class="form-control-checkbox">
            <label for="input_permission_<?php echo htmlspecialchars($capabilitySlug)?>"><?php echo $capabilityInf['title'] ?></label>
            <div class="fs_onoffswitch">
                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_permission_<?php echo htmlspecialchars($capabilitySlug)?>"<?php echo (isset($parameters['current_plan_capabilities'][$capabilitySlug]) && $parameters['current_plan_capabilities'][$capabilitySlug] == 'on') ? ' checked' : ''?>>
                <label class="fs_onoffswitch-label" for="input_permission_<?php echo htmlspecialchars($capabilitySlug)?>"></label>
            </div>
        </div>
    </div>
    <?php if( ! empty( $capabilityInf['children'] ) ): ?>
        <div class="form-groups-list">
            <?php foreach ( $capabilityInf['children'] AS $childSlug => $childInf ): ?>
                <div class="form-group pl-4">
                    <div class="form-control-checkbox">
                        <label for="input_permission_<?php echo htmlspecialchars($childSlug)?>"><?php echo $childInf['title'] ?></label>
                        <div class="fs_onoffswitch">
                            <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_permission_<?php echo htmlspecialchars($childSlug)?>"<?php echo (isset($parameters['current_plan_capabilities'][$childSlug]) && $parameters['current_plan_capabilities'][$childSlug] == 'on') ? ' checked' : '' ?>>
                            <label class="fs_onoffswitch-label" for="input_permission_<?php echo htmlspecialchars($childSlug)?>"></label>
                        </div>
                    </div>
                </div>
            <?php endforeach;?>
        </div>
    <?php endif;?>
<?php endforeach; ?>