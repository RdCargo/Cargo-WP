<?php
if (!defined('ABSPATH')) exit;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

$current_url = add_query_arg($_GET, admin_url('admin.php?page=cargo_shipments_table'));

// Replace 'paged' parameter value
$nextPageUrl = add_query_arg('paged', $data['current_page'] + 1, $current_url);
$prevPageUrl = add_query_arg('paged', $data['current_page'] - 1, $current_url);

$firstPageUrl = add_query_arg('paged', 1, $current_url);
$lastPageUrl = add_query_arg('paged', $data['total_pages'], $current_url);

?>
<div class="wrap">
    <h1><?php esc_html_e('Shipments Table', 'cargo-shipping-location-for-woocommerce')?></h1>
    <div class="cslfw-form-notice"></div>

    <form action="">
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Search shipments:</label>
            <input type="hidden" id="search-input" name="page" value="cargo_shipments_table">
            <input type="search" id="search-input" name="s" value="<?php echo esc_attr($search) ?>" placeholder="Type shipmentId here.">
            <input type="submit" id="search-submit" class="button" value="Search shipment">
        </p>
    </form>

    <div class="cargo-top-bar">

        <form class="js-shipments-table-actions">
            <div class="alignleft actions bulkactions" >
                <?php wp_nonce_field('cslfw-shipment-bulk-actions'); ?>

                <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                <select name="cslfw_action" id="bulk-action-selector-top">
                    <option value="-1">Bulk actions</option>
                    <option value="get_multiple_shipment_labels">Print label.</option>
                </select>
                <input type="submit" id="cslfw_doaction" class="button action" value="Apply">
            </div>
        </form>

        <div class="pagination">
            <div class="tablenav bottom">
                <div class="alignleft actions">
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $data['total_orders'] ?> orders</span>
                    <span class="pagination-links">
                <?php if ($data['current_page'] == 1) : ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                <?php else : ?>
                    <a class="next-page button" href="<?php echo esc_url($firstPageUrl) ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">«</span></a>
                    <a class="last-page button" href="<?php echo esc_url($prevPageUrl) ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">‹</span></a>
                <?php endif ?>

                <span class="screen-reader-text">Current Page</span>
                <span id="table-paging" class="paging-input">
                    <span class="tablenav-paging-text"><?php echo wp_kses_post($data['current_page']) ?> of <span class="total-pages"><?php echo wp_kses_post($data['total_pages']) ?></span></span>
                </span>

                <?php if ($data['current_page'] == $data['total_pages']) : ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                <?php else : ?>
                    <a class="next-page button" href="<?php echo esc_url($nextPageUrl) ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
                    <a class="last-page button" href="<?php echo esc_url($lastPageUrl) ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
                <?php endif ?>

            </span>
                </div>
                <br class="clear">
            </div>
        </div>
    </div>

    <form class="js-cargo-shipments-data">
        <table class="cargo-table  wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column">
                    <input type="checkbox">
                    <label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>
                </td>
                <th class="manage-column"><?php esc_html_e('OrderID', 'cargo-shipping-location-for-woocommerce')?></th>
                <th class="manage-column"><?php esc_html_e('Address', 'cargo-shipping-location-for-woocommerce')?></th>
                <th class="manage-column"><?php esc_html_e('ShipmentID', 'cargo-shipping-location-for-woocommerce')?></th>
                <th class="manage-column"><?php esc_html_e('Status', 'cargo-shipping-location-for-woocommerce')?></th>
                <th class="manage-column"><?php esc_html_e('Created At', 'cargo-shipping-location-for-woocommerce')?></th>
                <th class="manage-column"><?php esc_html_e('Printed Label', 'cargo-shipping-location-for-woocommerce')?></th>
                <th class="manage-column"><?php esc_html_e('Actions', 'cargo-shipping-location-for-woocommerce')?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data['orders'] as $order) : ?>
            <?php
                $shipmentData = $order->get_meta('cslfw_shipping');
                $printedLabel = $order->get_meta('cslfw_printed_label');

                $customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                if ($order->get_shipping_address_1() && $order->get_shipping_address_2() && $order->get_shipping_city()) {
                    $address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() . ',' . $order->get_shipping_city();
                } else {
                    $address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . ',' . $order->get_billing_city();
                }

                $nonce = wp_create_nonce('cslfw_cargo_actions' . $order->get_id());
            ?>
        <?php
        if (is_array($shipmentData)) :
            foreach($shipmentData as $shipmentId => $shipment) :

                $createdAt = $shipment['created_at'] ?? $order->get_date_created();
                ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input id="cb-select-<?php echo esc_attr($shipmentId) ?>" type="checkbox" name="shipments[]" value="<?php echo esc_attr($shipmentId) ?>">
                    </th>
                    <td class="column-primary">
                        <a href="<?php echo esc_url($order->get_edit_order_url()) ?>" target="_blank">
                            <strong>#<?php echo wp_kses_post($order->get_id())?></strong>
                            <strong><?php echo wp_kses_post($customerName)?></strong>
                        </a>
                    </td>
                    <td class="">
                        <?php echo wp_kses_post($address)?>
                    </td>
                    <td class="">
                        <div class="badge shipment"><?php echo wp_kses_post($shipmentId) ?></div>
                    </td>
                    <td class="">
                        <div class="">
                            <div class="cslfw-status status-<?php echo wp_kses_post($shipment['status']['number'])?>">
                                <?php echo wp_kses_post($shipment['status']['text']) ?>
                            </div>
                        </div>

                    </td>
                    <td class="">
                        <?php echo wp_kses_post($createdAt) ?>
                    </td>
                    <td class="">
                        <?php echo wp_kses_post($printedLabel) ?>
                    </td>
                    <td class="">

                        <button class="btn btn-yellow js-print-shipment-label"
                                data-nonce="<?php echo esc_attr($nonce) ?>"
                                data-order-id="<?php echo wp_kses_post($order->get_id()) ?>"
                                data-shipment-id="<?php echo wp_kses_post($shipmentId) ?>">הדפס תווית</button>
                        <button class="btn btn-green js-check-shipment-status"
                                data-nonce="<?php echo esc_attr($nonce) ?>"
                                data-order-id="<?php echo wp_kses_post($order->get_id()) ?>"
                                data-shipment-id="<?php echo wp_kses_post($shipmentId) ?>">בדוק מצב הזמנה</button>
                    </td>
                </tr>
            <?php
                    endforeach;
                endif;
            ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    </form>
    <div class="pagination">
        <div class="tablenav bottom">
            <div class="alignleft actions">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $data['total_orders'] ?> orders</span>
                <span class="pagination-links">
                    <?php if ($data['current_page'] == 1) : ?>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                    <?php else : ?>
                        <a class="next-page button" href="<?php echo esc_url($firstPageUrl) ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">«</span></a>
                        <a class="last-page button" href="<?php echo esc_url($prevPageUrl) ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">‹</span></a>
                    <?php endif ?>

                    <span class="screen-reader-text">Current Page</span>
                    <span id="table-paging" class="paging-input">
                        <span class="tablenav-paging-text"><?php echo wp_kses_post($data['current_page']) ?> of <span class="total-pages"><?php echo wp_kses_post($data['total_pages']) ?></span></span>
                    </span>

                    <?php if ($data['current_page'] == $data['total_pages']) : ?>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                    <?php else : ?>
                        <a class="next-page button" href="<?php echo esc_url($nextPageUrl) ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
                        <a class="last-page button" href="<?php echo esc_url($lastPageUrl) ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
                    <?php endif ?>

                </span>
            </div>
                <br class="clear">
        </div>
    </div>
</div>


<div class="cslfw_modal hidden" id="print-labels">
    <div class="cslfw_modal-content">
        <button type="button" class="close js-modal-close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <form class="js-print-multiple-labels">
            <?php wp_nonce_field('cslfw-print-multiple-labels'); ?>

            <h2>Choose format</h2>

            <label for="a4">
                <span>A4</span>
                <input id="a4" type="radio" name="printType" value="A4">
            </label>

            <label for="stickers">
                <span>Stickers</span>
                <input id="stickers" type="radio" name="printType" value="stickers" checked>
            </label>

            <div class="js-a4-option hidden">
                <h2>Choose starting point</h2>
                <div class="grid grid-cols-2" style="direction: rtl">
                    <?php for ($i = 1; $i <= 8; $i++) : ?>
                        <div class="grid-item a4-start">
                            <label for="startingPoint-<?php echo $i ?>">
                                <input type="radio"
                                       name="startingPoint"
                                       id="startingPoint-<?php echo $i ?>"
                                       value="<?php echo $i ?>"
                                    <?php if ($i === 1) echo 'checked' ?>
                                >
                                <span></span>
                            </label>
                        </div>
                    <?php endfor; ?>
                </div>

            </div>

            <p class="submit" style="text-align: center;">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Print Labels">
            </p>
        </form>
    </div>
</div>
<?php
