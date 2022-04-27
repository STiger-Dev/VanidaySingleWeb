<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;

/**
 * @param $allStuf
 * @param int $staffId
 * @param string $price
 * @param string $deposit
 * @param string $deposit_type
 * @param string $servicePrice
 * @param bool $display
 * @var mixed $parameters
 */

function employeeTpl($allStuf, $staffId = 0, $price = '', $deposit = '', $deposit_type = '', $servicePrice = '', $display = false )
{
    ?>
    <div class="form-row employee-tpl<?php echo $display?'':' hidden'?>">
        <div class="form-group col-md-4">
            <select class="form-control employee_select">
                <?php
                foreach( $allStuf AS $staffInf )
                {
                    echo '<option value="' . (int)$staffInf['id'] . '"' . ( $staffId == $staffInf['id'] ? ' selected' : '' ) . '>' . htmlspecialchars( $staffInf['name'] ) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="form-group col-md-3">
            <div class="change_price_checkbox_d">
                <input type="checkbox" class="change_price_checkbox" id="change_price_checkbox_<?php echo $staffId?>"<?php echo $price != -1 && $price !== '' ? ' checked' : ''?>>
                <label for="change_price_checkbox_<?php echo $staffId?>"><?php echo bkntc__('Specific price')?></label>
            </div>
        </div>

        <div class="form-group col-md-4 hidden">
            <div class="input-group">
                <input class="form-control except_price_input" title="Price" placeholder="0" value="<?php echo $price == -1 ? Math::floor( $servicePrice ) : Math::floor( $price )?>">
                <input class="form-control except_deposit_input" title="Deposit" placeholder="0" value="<?php echo $price == -1 ? 100 : Math::floor( $deposit )?>">
                <select class="form-control except_deposit_type_input">
                    <option value="percent"<?php echo $deposit_type=='percent' ? ' selected' : ''?>>%</option>
                    <option value="price"<?php echo $deposit_type=='price' ? ' selected' : ''?>><?php echo htmlspecialchars( Helper::currencySymbol() )?></option>
                </select>
            </div>
        </div>

        <div class="col-md-1">
            <img src="<?php echo Helper::assets('icons/unsuccess.svg')?>" class="delete-employee-btn">
        </div>
    </div>
    <?php
}

?>

<div class="staff_list_area">
    <?php
    $maxStuffId = 1;
    foreach ($parameters['service_staff'] AS $staffId => $price)
    {
        employeeTpl( $parameters['staff'], $staffId, $price['price'], $price['deposit'], $price['deposit_type'], $parameters['service']['price'], true );
        $maxStuffId = $staffId > $maxStuffId ? $staffId : $maxStuffId;
    }
    ?>
</div>
<div class="add-employee-btn"><i class="fas fa-plus-circle"></i> <?php echo bkntc__('Add staff')?></div>

<?php

echo employeeTpl($parameters['staff']);

?>

<script>var startCount = <?php echo $maxStuffId?>;</script>
