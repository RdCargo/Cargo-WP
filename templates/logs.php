<?php
/**
 * Admin View: Page - Status Logs
 *
 */
if ( ! defined( 'ABSPATH' ) || !class_exists('CSLFW_Logs') ) {
	exit;
}
$logs = (new CSLFW_Logs())->get_logs();
?>
<style>
.optimum {
    margin: 10px 2px 0 20px;
}
.optimum .optimum-logs #log-viewer-select {
    padding: 10px 0 8px;
    line-height: 28px;
}
.optimum .optimum-logs #log-viewer {
	background: #fff;
	border: 1px solid #e5e5e5;
	box-shadow: 0 1px 1px rgb(0 0 0 / 4%);
	padding: 5px 20px;

}
.optimum .optimum-logs a.page-title-action {
    display:inline-block;
    margin-right: 4px;
    padding: 4px 8px;
    position: relative;
    top: -3px;
    text-decoration: none;
    border: 1px solid #2271b1;
    border-radius: 2px;
    text-shadow: none;
    font-weight: 600;
    font-size: 13px;
    line-height: normal;
    color: #2271b1;
    background: #f6f7f7;
    cursor: pointer;
}
</style>

<script>
jQuery( function ( $ ) {
	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( 'Are you sure you want to delete this log?' );
	});
});
</script>
<div class="optimum">
	<div class="optimum-logs">
		<?php if ( $logs->files ) : ?>
			<div id="log-viewer-select">
				<div class="alignleft">
					<h2>
						<?php echo esc_html( $logs->current_view ); ?>
						<?php if ( ! empty( $logs->current_view ) ) : ?>
							<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' =>  sanitize_title( $logs->current_view ) ), admin_url( 'admin.php?page=cargo_shipping_log' ) ), 'remove_log' ) ); ?>" class="button"><?php esc_html_e( 'Delete log', 'cargo-shipping-location-for-woocommerce' ); ?></a>
						<?php endif; ?>
					</h2>
				</div>
				<div class="alignright">
					<form action="<?php echo esc_url( admin_url( 'admin.php?page=cargo_shipping_log' ) ); ?>" method="post">
						<select name="log_file">
							<?php foreach ( $logs->files as $log_key => $log_file ) : ?>
								<?php
									$timestamp = filemtime( $logs->logs_dir .'/'. $log_file );
									/* translators: 1: last access date 2: last access time */
									$date = sprintf( __( '%1$s at %2$s', 'cargo-shipping-location-for-woocommerce' ), date_i18n( wc_date_format(), $timestamp ), date_i18n( wc_time_format(), $timestamp ) );
								?>
								<option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $logs->current_view ), $log_key ); ?>><?php echo esc_html( $log_file ); ?> (<?php echo esc_html( $date ); ?>)</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button" value="<?php esc_attr_e( 'View', 'cargo-shipping-location-for-woocommerce' ); ?>"><?php esc_html_e( 'View', 'cargo-shipping-location-for-woocommerce' ); ?></button>
					</form>
				</div>
				<div class="clear"></div>
			</div>
			<div id="log-viewer">
				<pre><?php echo esc_html( file_get_contents( $logs->logs_dir .'/'. $logs->current_view ) ); ?></pre>
			</div>
		<?php else : ?>
			<div class="updated woocommerce-message inline"><p><?php esc_html_e( 'There are currently no logs to view.', 'cargo-shipping-location-for-woocommerce' ); ?></p></div>
		<?php endif; ?>
	</div>
</div>
