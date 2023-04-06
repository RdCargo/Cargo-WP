<?php
/**
 * Admin View: Page - Contact Us
 *
 */

if ( ! defined( 'ABSPATH' ) || !class_exists('CSLFW_Contact') ) {
    exit;
}
?>
<h1>Contact Us</h1>
<style>
    .cslfw__contact {
        max-width: 400px;
        width: 100%;
    }
    .cslfw__contact label {
        display: block;
        font-weight: 600;
    }
    .cslfw__contact .input-field input,
    .cslfw__contact .input-field textarea {
        width: 100%;
    }
    .cslfw__contact .input-field {
        margin-bottom: 15px;
        width: 100%;
    }
</style>
<form class="cslfw__contact cslfw-send-email">
    <div class="input-field">
        <label for="reason"><?php esc_html_e('Reason', 'cargo-shipping-location-for-woocommerce') ?></label>
        <input type="text" id="reason" name="reason" required>
    </div>
    <div class="input-field">
        <label for="email"><?php esc_html_e('Your Email', 'cargo-shipping-location-for-woocommerce') ?></label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="input-field">
        <label for="content"><?php esc_html_e('Explain in details', 'cargo-shipping-location-for-woocommerce') ?></label>
        <textarea name="content" id="content" cols="30" rows="10" required></textarea>
    </div>
    <button type="submit" class="button" value="<?php esc_attr_e( 'Send', 'woocommerce' ); ?>"><?php esc_html_e( 'Send', 'woocommerce' ); ?></button>
</form>
