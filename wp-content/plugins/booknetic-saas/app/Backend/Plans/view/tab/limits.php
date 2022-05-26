<?php

defined( 'ABSPATH' ) or die();

?>

<?php foreach ( $parameters['limits_list'] as $limitSlug => $limitInf ): ?>
    <div class="form-group form-group-inline-dotted">
        <label for="input_limit_<?php echo htmlspecialchars($limitSlug)?>"><?php echo $limitInf['title']?></label>
        <input class="form-control permission-limit" id="input_limit_<?php echo htmlspecialchars($limitSlug)?>" value="<?php echo isset($parameters['current_plan_limits'][$limitSlug]) ? (int)$parameters['current_plan_limits'][$limitSlug] : -1;?>">
    </div>
<?php endforeach; ?>