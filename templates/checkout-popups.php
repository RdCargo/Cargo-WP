<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<?php
    $cargo_size         = get_option('cslfw_map_size');
    $cargo_size_custom  = get_option('cslfw_custom_map_size');
    $cargo_size_custom  = $cargo_size === 'map_custom' ? "style=width:$cargo_size_custom" : '';
    $maps_key = get_option('cslfw_google_api_key');
?>
<?php if ($maps_key) : ?>
<input type="hidden" id="default_markers" value="<?php echo esc_url(CSLFW_URL.'assets/image/cargo-icon-svg.svg') ?>" >
<input type="hidden" id="selected_marker" value="<?php echo esc_url(CSLFW_URL.'assets/image/selected_new.png') ?>" >
<div class="modal hidden" id="mapmodelcargo" tabindex="-1" role="dialog">
    <div class="modal-dialog <?php echo esc_attr($cargo_size) ?>"  role="document">
        <div class="modal-content " <?php echo esc_attr($cargo_size_custom) ?>>
            <div class="modal-header">
                <div class="cargo-logo">
                    <img src="<?php echo CSLFW_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="60">
                </div>
                <div class="modal-search" style="direction: rtl;">
                    <a href="javascript:void(0);" class="open-how-it-works">?</a>
                    <div class="form-row">
                        <div class='cargo_input_div'>
                            <input type='text' id='cargo-location-input' placeholder='יש להזין את הכתובת שלך על מנת להציג נקודות חלוקה קרובות..''>
                            <button class='cargo_address_check'>איתור</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="close js-modal-close" id="modal-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class='cargo_content_main'>
                    <a class='js-toggle-locations-list' style='display: none'>סגירה</a>
                    <div class='cargo_location_list'>
                        <div class='cargo_list_msg'>יש מלא את כתובת שלך בשדה החיפוש</div>
                    </div>
                    <div id="map" ></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal descript" tabindex="-1" role="dialog" style="margin-top: 0px;display:none;    z-index: 2222222222;" >
    <div class="modal-dialog" role="document" style="max-width: 700px; width: 100%;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="cargo-logo">
                    <img src="<?php echo CSLFW_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="60">
                </div>
                <h5 class="modal-title"><?php esc_html_e('CARGO BOX - איך זה עובד', 'cargo-shipping-location-for-woocommerce') ?></h5>
                <button type="button" class="close js-modal-close" id="modal-close-desc" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="direction: rtl;">
                <div><?php esc_html_e(' CARGO BOX ', 'cargo-shipping-location-for-woocommerce') ?></div>
                <div><?php esc_html_e('נקודות החלוקה שלנו בפריסה ארצית לנוחיותכם,', 'cargo-shipping-location-for-woocommerce') ?></div>
                <div><p><?php esc_html_e('אוספים את החבילה בדרך הקלה והמהירה ביותר!', 'cargo-shipping-location-for-woocommerce') ?></p></div>
                <div><p><?php esc_html_e('איסוף החבילה שלכם יתבצע בנקודת חלוקה הקרובה לביתכם או למקום עבודתכם, היכן שתבחרו, ללא המתנה לשליח, ללא צורך בזמינות, בצורה היעילה, הזולה והפשוטה ביותר', 'cargo-shipping-location-for-woocommerce') ?></p></div>
                <div><?php esc_html_e('כמה פשוט? ככה פשוט-', 'cargo-shipping-location-for-woocommerce') ?></div>
                <div><?php esc_html_e('בוחרים נקודת חלוקה שמתאימה לכם', 'cargo-shipping-location-for-woocommerce') ?></div>
                <div><?php esc_html_e('כאשר החבילה שלכם מגיעה ליעד אתם מקבלים SMS ומייל ', 'cargo-shipping-location-for-woocommerce') ?></div>
                <div><?php esc_html_e('ומגיעים לאסוף את החבילה ', 'cargo-shipping-location-for-woocommerce') ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
