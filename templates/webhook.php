<h2><?php _e('Webhook setup', 'cargo-shipping-location-for-woocommerce') ?></h2>
<div class="wrap">
    <div id="message">
        <p><strong><?php _e('You need to enter api key, provided by cargo in order to set up the status update webhooks.', 'cargo-shipping-location-for-woocommerce') ?></strong></p>
    </div>

    <div class="mb-4">
        <form class="flex cslfw-save-api-key ">
            <div class="">
                <label for="cslfw_api_key">Cargo Api Key</label>
                <input type="text"
                       id="cslfw_api_key"
                       class="regular-text"
                       name="cslfw_api_key"
                       value="<?php echo esc_attr($data['apiKey']) ?>">
            </div>
            <button type="submit"
                    class="button button-primary"><?php esc_html_e( 'Save', 'cargo-shipping-location-for-woocommerce' ); ?></button>
        </form>
    </div>


    <?php if (!empty($data['apiKey'])) : ?>
    <div id="message">
        <p><strong><?php _e('Clicking add webhooks you will get shipment statuses automatically. clicking remove, will disable that feature, and you will have to check statuses manually..', 'cargo-shipping-location-for-woocommerce') ?></strong></p>
    </div>
    <div class="flex">
        <button type="submit"
                class="cslfw-add-webhooks button button-primary">
            <?php if ($data['webhooksInstalled']) : ?>
                <?php esc_html_e( 'Update webhooks', 'cargo-shipping-location-for-woocommerce' ); ?>
            <?php else : ?>
                <?php esc_html_e( 'Add webhooks', 'cargo-shipping-location-for-woocommerce' ); ?>
            <?php endif; ?>

        </button>
        <?php if ($data['webhooksInstalled']) : ?>
        <button type="submit"
                class="cslfw-remove-webhooks button"><?php esc_html_e( 'Remove webhooks', 'cargo-shipping-location-for-woocommerce' ); ?></button>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <div class="cslfw-form-notice"></div>
</div>

<style>
    .flex {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mb-4 {
        margin-bottom: 12px;
    }
</style>