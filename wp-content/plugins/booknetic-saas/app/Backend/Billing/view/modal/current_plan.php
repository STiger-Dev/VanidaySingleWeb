<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */
?>

<div class="p-5">
    <h3 class="text-center font-weight-normal"><?php echo $parameters['plan']->name ?></h3>
    <?php foreach ( $parameters['limits'] as $key => $value ):?>
    <div class="d-flex justify-content-between border-bottom p-1">
        <div>
            <?php echo $value['title'] ?>
        </div>
        <div>
            <?php echo $value['current_usage'] . ' / ' . ($value['max_usage'] == -1 ? '∞' : $value['max_usage']) ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="modal_actions text-center mt-4">
        <button class="btn btn-lg btn-outline-secondary" type="button" data-dismiss="modal"><?php echo bkntc__('CLOSE')?></button>
    </div>
</div>