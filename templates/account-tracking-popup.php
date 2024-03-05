<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="modal order-tracking-model" tabindex="-1" role="dialog" style="display: none;">
    <div class="modal-dialog" role="document" style="max-width: 1000px; width: 100%;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="cargo-logo">
                    <img src="<?php echo esc_url(CSLFW_URL.'assets/image/howitworks.png'); ?>" alt="Cargo" width="60">
                </div>

                <h5 class="modal-title"><?php esc_html_e('Order Tracking', 'cargo-shipping-location-for-woocommerce') ?></h5>
                <button type="button" class="close js-modal-close" id="modal-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body order-details-ajax">
                <div class="delivery-status" style="padding: 10px 20px; display: none;"></div>
                <div class="delivery-error woocommerce-error" style="display: none;"></div>
            </div>
            <div class="modal-footer" style="display: block;">
                <div id="FlyingCargo_footer" style="display: none;"><?php esc_html_e('נקודת איסוף מסומנת:', 'cargo-shipping-location-for-woocommerce') ?>
                    <div id="FlyingCargo_loc_name"></div>
                    <button type="button"
                            class="selected-location btn button wp-element-button"
                            id="FlyingCargo_confirm"
                            data-lat=""
                            data-long=""
                            data-fullAdd=""
                            data-disctiPointID=""
                            data-pointName=""
                            data-city=""
                            data-street=""
                            data-streetNum=""
                            data-comment=""
                            data-locationName=""><?php esc_html_e('בחירה וסיום', 'cargo-shipping-location-for-woocommerce') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
