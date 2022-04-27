<button
        data-location="<?php echo (isset($atts['location'])     && is_numeric($atts['location']) )   ? htmlspecialchars($atts['location']) : ''; ?>"
        data-theme="<?php echo (isset($atts['theme'])           && is_numeric($atts['theme']) )      ? htmlspecialchars($atts['theme']) : ''; ?>"
        data-category="<?php echo (isset($atts['category'])     && is_numeric($atts['category']) )   ? htmlspecialchars($atts['category']) : ''; ?>"
        data-staff="<?php echo (isset($atts['staff'])           && is_numeric($atts['staff']) )      ? htmlspecialchars($atts['staff']) : ''; ?>"
        data-service="<?php echo (isset($atts['service'])       && is_numeric($atts['service']) )    ? htmlspecialchars($atts['service']) : ''; ?>"
        class='bnktc_booking_popup_btn <?php echo isset($atts['class']) ? htmlspecialchars($atts['class']) : "" ?>'
        <?php echo isset($atts['style']) ? 'style="'. htmlspecialchars($atts['style']) .'"' : '' ?>>
    <?php echo isset($atts['caption']) ? htmlspecialchars($atts['caption']) : 'Book now' ;?>
</button>
