<?php
if ( ! defined( 'ABSPATH' ) ) exit;

    $order = $data['order'];
    $paymentMethod = $order->get_payment_method();
    $nonce = wp_create_nonce('cslfw_cargo_actions'.$order->get_id());

    $codTypes = [
        '0' => esc_html__('Cash (Default)', 'cargo-shipping-location-for-woocommerce'),
        '1' => esc_html__('Cashier\'s check', 'cargo-shipping-location-for-woocommerce'),
        '2' => esc_html__('Check', 'cargo-shipping-location-for-woocommerce'),
        '3' => esc_html__('All Payment Methods', 'cargo-shipping-location-for-woocommerce')
    ];
?>
<input type="hidden" id="cslfw_cargo_actions_nonce" value="<?php echo esc_attr($nonce); ?>">
<div class="cargo-butoon">
    <button class="button button-primary cslfw-change-carrier-id" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
        <?php if ($data['shippingMethod'] === 'woo-baldarp-pickup') : ?>
            <?php esc_html_e('Switch to express', 'cargo-shipping-location-for-woocommerce' ) ?>
            <?php else : ?>
            <?php esc_html_e('Switch to box shipment', 'cargo-shipping-location-for-woocommerce' ) ?>
        <?php endif; ?>
    </button>
</div>

<div class="cargo-submit-form-wrap" <?php if ( $data['shipmentIds'] ) echo 'style="display: none;"'; ?> >
    <?php if (!$data['fulfillAllShipments']) { ?>
        <div class="cargo-button">
            <strong><?php esc_html_e('Fulfillment (SKU * Quantity in Notes)', 'cargo-shipping-location-for-woocommerce') ?></strong>
            <label for="cslfw_fulfillment">
                <input type="checkbox" name="cslfw_fulfillment" id="cslfw_fulfillment" />
                <span><?php esc_html_e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
            </label>
        </div>
    <?php } ?>
    <?php if ($data['shippingMethod'] !== 'woo-baldarp-pickup' || $data['shippingMethod']) : ?>
        <div class="cargo-button">
            <strong><?php esc_html_e('Double Delivery', 'cargo-shipping-location-for-woocommerce') ?></strong>
            <label for="cargo_double-delivery">
                <input type="checkbox" name="cargo_double_delivery" id="cargo_double-delivery" />
                <span><?php esc_html_e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
            </label>
        </div>
        <div class="cargo-button">
            <strong><?php esc_html_e('Cash on delivery', 'cargo-shipping-location-for-woocommerce') ?> (<?php echo wp_kses_post($order->get_formatted_order_total()) ?>)</strong>
            <label for="cargo_cod">
                <input type="checkbox" name="cargo_cod" id="cargo_cod" <?php if ($paymentMethod === $data['paymentMethodCheck']) echo esc_attr('checked'); ?> />
                <span><?php esc_html_e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
            </label>
        </div>

        <div class="cargo-button cargo_cod_type" style="display: <?php echo esc_html($paymentMethod === $data['paymentMethodCheck'] ? 'block' : 'none' ) ?>">
            <strong><?php esc_html_e('Cash on delivery Type', 'cargo-shipping-location-for-woocommerce') ?></strong>
            <?php foreach ($codTypes as $key => $value) : ?>
                <label for="cargo_cod_type_<?php echo esc_attr($key) ?>">
                    <input type="radio" name="cargo_cod_type" id="cargo_cod_type_<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($key) ?>" />
                    <span><?php echo esc_html($value) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($data['shippingMethod'] === 'woo-baldarp-pickup') : ?>
        <?php
        $selectedPoint = $data['selectedPoint'];

        if ( $selectedPoint ) :
            $cities = $data['cities'];
            $selectedCity = $data['selectedPoint']->CityName;
            if ($cities) { ?>
                <p class="form-row form-row-wide">
                    <label for="cargo_city">
                        <span><?php esc_html_e('בחירת עיר', 'cargo-shipping-location-for-woocommerce') ?></span>
                    </label>

                    <select name="cargo_city" id="cargo_city" class="">
                        <option><?php esc_html_e('נא לבחור עיר', 'cargo-shipping-location-for-woocommerce') ?></option>
                        <?php foreach ($cities as $city) : ?>
                            <option value="<?php echo esc_attr($city) ?>" <?php if (trim($selectedCity) === trim($city) ) echo esc_attr('selected="selected"'); ?>><?php echo esc_html($city) ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php }
            ?>
            <div class="form-row form-row-wide">
                <p class="select-wrap w-100">
                    <label for="cargo_pickup_point">
                        <span><?php esc_html_e('בחירת נקודת חלוקה', 'cargo-shipping-location-for-woocommerce') ?></span>
                    </label>
                    <select name="cargo_pickup_point" id="cargo_pickup_point" class=" w-100" style="display: <?php echo esc_attr($data['points'] ? 'block' : 'none'); ?>" >
                        <?php foreach ($data['points'] as $key => $point) : ?>
                            <option value="<?php echo esc_attr($point->DistributionPointID) ?>" <?php if ($selectedPoint?->DistributionPointID === $point->DistributionPointID) echo 'selected="selected"' ?>>
                                <?php echo esc_html($point->DistributionPointName) ?>, <?php echo esc_html($point->CityName) ?>, <?php echo esc_html($point->StreetName) ?> <?php echo esc_html($point->StreetNum) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            </div>
        <?php
        endif; ?>
    <?php endif; // End express check ?>
    <div class="cargo-radio">
        <strong><?php esc_html_e('Shipment Type', 'cargo-shipping-location-for-woocommerce') ?></strong>
        <label for="cargo_shipment_type_regular">
            <input type="radio" name="cargo_shipment_type" id="cargo_shipment_type_regular" checked value="1" />
            <span><?php esc_html_e('Regular', 'cargo-shipping-location-for-woocommerce') ?></span>
        </label>
        <?php if ($data['shippingMethod'] !== 'woo-baldarp-pickup' || $data['shippingMethod'] ) : ?>
            <label for="cargo_shipment_type_pickup">
                <input type="radio" name="cargo_shipment_type" id="cargo_shipment_type_pickup" value="2" />
                <span><?php esc_html_e('Pickup', 'cargo-shipping-location-for-woocommerce') ?></span>
            </label>
        <?php endif; ?>
    </div>

    <div class="cargo-button">
        <strong><?php esc_html_e('Packages', 'cargo-shipping-location-for-woocommerce') ?></strong>
        <input type="number" name="cargo_packages" id="cargo_packages" value="1" min="1" max="100" style="max-width: 80px;"/>
    </div>

    <div class="cargo-button">
        <a href="#"
           class="submit-cargo-shipping  btn btn-success"
           data-id="<?php echo esc_attr($order->get_id()); ?>"><?php esc_html_e('שלח ל CARGO', 'cargo-shipping-location-for-woocommerce') ?></a>
    </div>
</div>


<?php if ( $data['shipmentIds'] ) :
    $cargoShippingIds =  implode(', ', $data['shipmentIds']);
    ?>

    <div class="cargo-button" style="margin-top: 10px;">
        <a href="#" class="label-cargo-shipping button"  data-order-id="<?php echo esc_attr($order->get_id()); ?>"><?php esc_html_e('הדפס תווית', 'cargo-shipping-location-for-woocommerce') ?></a>
    </div>

    <div class="checkstatus-section">
        <?php
        $webhook_installed = get_option('cslfw_webhooks_installed');
        foreach ($data['shipmentData'] as $key => $value) {
            echo wp_kses_post('<div class=""><p class="cslfw-status status-' . $value['status']['number'] .'">'. $key .' - ' . $value['status']['text'] . '</p></div>');

            if ($webhook_installed !== 'yes') {
                echo wp_kses_post("<a href='#' class='btn btn-success send-status button' style='margin-bottom: 10px;' data-id=" . $order->get_id() . " data-deliveryid='$value'>" . esc_html_e('בקש סטטוס משלוח', 'cargo-shipping-location-for-woocommerce') . " $key</a>");
            }
        }
        ?>
    </div>

    <div class="cargo-button">
        <a href="#" class="cslfw-create-new-shipment button button-primary"><?php esc_html_e('יצירת משלוח חדש', 'cargo-shipping-location-for-woocommerce') ?></a>
        <p style="font-size: 12px;"><?php esc_html_e('פעולה זו לא תבטל את המשלוח הקודם (יש לפנות לשירות הלקוחות) אלא תיצור משלוח חדש', 'cargo-shipping-location-for-woocommerce') ?></p>
    </div>
<?php endif; ?>

<?php if ($data['shippingMethod'] === 'woo-baldarp-pickup' && $data['shipmentIds']) {
    $boxShipmentType = $order->get_meta('cslfw_box_shipment_type', true);

    foreach ($data['shipmentData'] as $shipping_id => $data) {
        $cargo = new \CSLFW\Includes\CargoAPI\Cargo();
        $point = $cargo->findPointById($data['box_id']);

        if ($point) { ?>
            <div>
                <h3>SHIPPING <?php wp_kses_post($shipping_id) ?></h3>
                <h4 style="margin-bottom: 5px;"><?php esc_html_e('Cargo Point Details', 'cargo-shipping-location-for-woocommerce') ?></h4>
                <?php if ($boxShipmentType === 'cargo_automatic' && !$point) { ?>
                    <p><?php esc_html_e('Details will appear after sending to cargo.', 'cargo-shipping-location-for-woocommerce') ?></p>
                <?php } else { ?>
                    <h2 style="padding:0;">
                        <strong><?php echo wp_kses_post($point->DistributionPointName) ?> : <?php echo wp_kses_post($point->DistributionPointID); ?></strong>
                    </h2>
                    <h4 style="margin:0;"><?php echo wp_kses_post("{$point->StreetNum} {$point->StreetName} {$point->CityName}") ?></h4>
                    <h4 style="margin:0;"><?php echo wp_kses_post($point->Comment) ?></h4>
                    <h4 style="margin:0;"><?php echo wp_kses_post($point->Phone) ?></h4>
                <?php } ?>
            </div>
        <?php }
    } ?>

<?php }
