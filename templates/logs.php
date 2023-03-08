<?php
/**
 * Admin View: Page - Status Logs
 *
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$OptimumOctopus = new CSLFW_Shipping;

$upload = wp_upload_dir();
$upload_dir = $upload['basedir'];
$logs_dir = $upload_dir . '/cargo-shipping-location';

$files  = @scandir( $logs_dir ); // @codingStandardsIgnoreLine.

$result = array();
$handles = array();

if ( ! empty( $files ) ) {
	foreach ( $files as $key => $value ) {
		if ( ! in_array( $value, array( '.', '..' ), true ) ) {
			if ( ! is_dir( $value ) && strstr( $value, '.txt' ) ) {
				$result[ sanitize_title( $value ) ] = $value;
			}
		}
	}
}

if ( ! empty( $_REQUEST['log_file'] ) && isset( $result[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ] ) ){ // WPCS: input var ok, CSRF ok.
	$viewed_log = $result[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ]; // WPCS: input var ok, CSRF ok.
} elseif ( ! empty( $result ) ) {
	$viewed_log = current( $result );
}



$handle = ! empty( $viewed_log ) ? cslfw_get_log_file_handle_op( $viewed_log ) : '';

if ( ! empty( $_REQUEST['handle'] ) ) { // WPCS: input var ok, CSRF ok.
	cslfw_remove_log_op();
}

function cslfw_get_log_file_handle_op( $filename ) {
	return substr( $filename, 0, strlen( $filename ) > 48 ? strlen( $filename ) - 48 : strlen( $filename ) - 4 );
}

function cslfw_remove_log_op() {
	if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'remove_log' ) ) { // WPCS: input var ok, sanitization ok.
		wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
	}

	if ( ! empty( $_REQUEST['handle'] ) ) {  // WPCS: input var ok.
		cslfw_remove_op( wp_unslash( $_REQUEST['handle'] ) ); // WPCS: input var ok, sanitization ok.
	}

	wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=cargo_shipping_log' ) ) );
	exit();
}

function cslfw_remove_op($handle) {
	$upload = wp_upload_dir();
	$upload_dir = $upload['basedir'];
	$logs_dir = $upload_dir . '/cargo-shipping-location';
	$removed = false;
	//$logs    = $this->get_log_files();
	$files  = @scandir( $logs_dir ); // @codingStandardsIgnoreLine.
	$result_new = array();
	
	if ( ! empty( $files ) ) {
		foreach ( $files as $kye => $value ) {
			if ( ! in_array( $value, array( '.', '..' ), true ) ) {
				if ( ! is_dir( $value ) && strstr( $value, '.txt' ) ) {
					$result_new[ sanitize_title( $value ) ] = $value;
				}
			}
		}
	}
	
	$handle  = sanitize_title( $handle );
	
	if ( isset( $result_new[ $handle ] ) && $result_new[ $handle ] ) {
		$file = realpath( trailingslashit( $logs_dir ) .'/'. $result_new[ $handle ] );
		if ( 0 === stripos( $file, realpath( trailingslashit( $logs_dir ) ) ) && is_file( $file ) && is_writable( $file ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
			cslfw_close_op( $file ); // Close first to be certain no processes keep it alive after it is unlinked.
			$removed = unlink( $file ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink
		}
	}
	return $removed;
}

function cslfw_close_op( $handle ) {
	$result = false;

	if ( cslfw_is_open_op( $handle ) ) {
		$result = fclose( $handles[ $handle ] ); // @codingStandardsIgnoreLine.
		unset( $handles[ $handle ] );
	}
	return $result;
}

function cslfw_is_open_op($handle) {
	return array_key_exists( $handle, $handles ) && is_resource( $handles[ $handle ] );
}

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
		<?php if ( $result ) : ?>
			<div id="log-viewer-select">
				<div class="alignleft">
					<h2>
						<?php echo esc_html( $viewed_log ); ?>
						<?php if ( ! empty( $viewed_log ) ) : ?>
							<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' =>  sanitize_title( $viewed_log ) ), admin_url( 'admin.php?page=cargo_shipping_log' ) ), 'remove_log' ) ); ?>" class="button"><?php esc_html_e( 'Delete log', 'woocommerce' ); ?></a>
						<?php endif; ?>
					</h2>
				</div>
				<div class="alignright">
					<form action="<?php echo esc_url( admin_url( 'admin.php?page=cargo_shipping_log' ) ); ?>" method="post">
						<select name="log_file">
							<?php foreach ( $result as $log_key => $log_file ) : ?>
								<?php
									$timestamp = filemtime( $logs_dir .'/'. $log_file );
									/* translators: 1: last access date 2: last access time */
									$date = sprintf( __( '%1$s at %2$s', 'woocommerce' ), date_i18n( wc_date_format(), $timestamp ), date_i18n( wc_time_format(), $timestamp ) );
								?>
								<option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>><?php echo esc_html( $log_file ); ?> (<?php echo esc_html( $date ); ?>)</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button" value="<?php esc_attr_e( 'View', 'woocommerce' ); ?>"><?php esc_html_e( 'View', 'woocommerce' ); ?></button>
					</form>
				</div>
				<div class="clear"></div>
			</div>
			<div id="log-viewer">
				<pre><?php echo esc_html( file_get_contents( $logs_dir .'/'. $viewed_log ) ); ?></pre>
			</div>
		<?php else : ?>
			<div class="updated woocommerce-message inline"><p><?php esc_html_e( 'There are currently no logs to view.', 'woocommerce' ); ?></p></div>
		<?php endif; ?>
	</div>
</div>