<div>
	<h2><?php esc_html_e('Shipping API Location - Settings', 'cargo-shipping-location-for-woocommerce') ?></h2>
	<div class="wrap">
		<?php if( isset($_GET['settings-updated']) ) { ?>
			<div id="message" class="updated">
				<p><strong><?php esc_html_e('Congratulations settings are saved.', 'cargo-shipping-location-for-woocommerce') ?></strong></p>
			</div>
		<?php } ?>

		<form method="post" action="options.php" id="seting_cargo">
			<?php settings_fields( 'cslfw_shipping_api_settings_fg' ); ?>
			<table>

				<tr>
					<th scope="row" align="left" >
                        <label for="shipping_cargo_express"><?php esc_html_e('Cargo Express: ', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_cargo_express" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please Insert CARGO Express Code', 'cargo-shipping-location-for-woocommerce')?>"
                                       id="shipping_cargo_express"
                                       name="shipping_cargo_express"
                                       value="<?php echo esc_attr( get_option('shipping_cargo_express') ) ?>" autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row" align="left" >
                        <label for="shipping_cargo_box"><?php esc_html_e('Cargo BOX: ', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_cargo_box" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please Insert CARGO BOX code', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="shipping_cargo_box"
                                       name="shipping_cargo_box"
                                       value="<?php echo esc_attr( get_option('shipping_cargo_box') ) ?>" autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

                <tr>
                    <th scope="row" align="left" >
                        <label for="shipping_pickup_code"><?php esc_html_e('Cargo Pickup code: ', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="shipping_pickup_code" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please Insert pickup code', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="shipping_pickup_code"
                                       name="shipping_pickup_code"
                                       value="<?php echo esc_attr( get_option('shipping_pickup_code') ) ?>" autocomplete="off"/>
                            </label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row" align="left"  style="vertical-align: top;">
                        <label for="cargo_box_style"><?php esc_html_e('Cargo Box Checkout Style', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cargo_box_style" style="vertical-align: top;">
                                <?php
                                    $cargo_box_style = get_option('cargo_box_style');
                                    $cargo_box_style_options = array(
                                        'cargo_map'         => __('Map', 'cargo-shipping-location-for-woocommerce'),
                                        'cargo_dropdowns'   => __('Dropdowns', 'cargo-shipping-location-for-woocommerce'),
                                        'cargo_automatic'   => __('Automatic choice', 'cargo-shipping-location-for-woocommerce')
                                    );
                                ?>
                                <select name="cargo_box_style">
                                    <?php foreach ( $cargo_box_style_options as $key => $value ) : ?>
                                    <option value="<?php echo esc_attr($key) ?>" <?php if ($key === $cargo_box_style) echo esc_attr('selected="selected"'); ?>><?php echo $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <p style="margin-top: 0;">Automatic choice will choose closest pickup point automatically from customer address.</p>

                        </div>
                    </td>
                </tr>

				<tr class="cslfw-google-maps" style="display: <?php echo $cargo_box_style === 'cargo_map' || !$cargo_box_style ? 'table-row' : 'none' ?>">
					<th scope="row" align="left" style="vertical-align: top;">
                        <label for="cslfw-google-api-key"><?php esc_html_e('Google maps API key:', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cslfw_google_api_key" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please Google maps API key', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="cslfw_google_api_key"
                                       name="cslfw_google_api_key"
                                    <?php echo $cargo_box_style === 'cargo_map' ? 'required' : '' ?>
                                       value="<?php echo esc_attr( get_option('cslfw_google_api_key') )?>" />

                            </label>
                            <p style="margin-top: 0;">Please insert Google token, If you don’t have please follow <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">this guideline.</a></p>

                        </div>
					</td>
				</tr>
                <tr class="cslfw-google-maps" style="display: <?php echo $cargo_box_style === 'cargo_map' ? 'table-row' : 'none' ?>">
                    <th scope="row" align="left" >
                        <label for="cargo_box_style"><?php esc_html_e('Map size presets', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_map_size" style="vertical-align: top;">
                                <?php
                                $cargo_map_style = get_option('cslfw_map_size');
                                $cargo_map_style_options = array(
                                    'small'        => __('Small', 'cargo-shipping-location-for-woocommerce'),
                                    'middle'      => __('Middle size', 'cargo-shipping-location-for-woocommerce'),
                                    'wide'        => __('Wide', 'cargo-shipping-location-for-woocommerce'),
                                    'map_custom'  => __('Custom', 'cargo-shipping-location-for-woocommerce')
                                );
                                ?>
                                <select name="cslfw_map_size">
                                    <?php foreach ( $cargo_map_style_options as $key => $value ) : ?>
                                        <option value="<?php echo esc_attr($key) ?>" <?php if ($key === $cargo_map_style) echo esc_attr('selected="selected"'); ?>><?php echo $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr class="cslfw-map-size" style="display: <?php echo $cargo_map_style === 'map_custom' ? 'table-row' : 'none' ?>">
                    <th scope="row" align="left" >
                        <label for="cslfw_custom_map_size"><?php esc_html_e('Map custom size', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_custom_map_size" style="vertical-align: top;">
                                <?php
                                    $cargo_map_custom_size = get_option('cslfw_custom_map_size');
                                ?>
                                <input type="text" name="cslfw_custom_map_size" value="<?php echo esc_attr($cargo_map_custom_size) ?>" placeholder="px,%,vw or any units">
                            </label>
                        </div>
                    </td>
                </tr>
				<tr>
					<th scope="row" align="left" >
                        <label for="from_street"><?php esc_html_e('From Street Number', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_street" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="Please enter from street number"
                                       id="from_street" name="from_street"
                                       value="<?php echo esc_attr( get_option('from_street') )?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="from_street"><?php esc_html_e('Street Name', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_street_name" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please enter from street Name', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="from_street_name"
                                       name="from_street_name"
                                       value="<?php echo esc_attr( get_option('from_street_name') ) ?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="from_city"><?php esc_html_e('From City', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_city" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please enter from City', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="from_street_name"
                                       name="from_city"
                                       value="<?php echo esc_attr( get_option('from_city') )?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" ><label for="phonenumber_from"><?php esc_html_e('Phone Number', 'cargo-shipping-location-for-woocommerce') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="phonenumber_from" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please enter Phone Number', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="phonenumber_from"
                                       name="phonenumber_from"
                                       value="<?php echo esc_attr( get_option('phonenumber_from') )?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="website_name_cargo"><?php esc_html_e('Website name', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td>
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="website_name_cargo" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php esc_html_e('Please Enter Your Website Name', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="website_name_cargo"
                                       name="website_name_cargo"
                                       value="<?php echo esc_attr( get_option('website_name_cargo') ) ?>" required autocomplete="off"/>
                            </label>
						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>
				</tr>
                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_auto_shipment_create"><?php esc_html_e('Automatically create shipment.', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_auto_shipment_create" style="vertical-align: top;">
                                <?php
                                $auto_shipment_create = get_option('cslfw_auto_shipment_create');
                                $checked = $auto_shipment_create ? 'checked' : '';
                                ?>
                                <label for="cslfw_auto_shipment_create">
                                    <input type="checkbox" id="cslfw_auto_shipment_create" name="cslfw_auto_shipment_create" <?php echo esc_attr($checked) ?>>
                                    <span><?php esc_html_e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_fulfill_all"><?php esc_html_e('Fullfill all orders', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_fulfill_all" style="vertical-align: top;">
                                <?php
                                    $cslfw_fulfill_all = get_option('cslfw_fulfill_all');
                                    $checked = $cslfw_fulfill_all ? 'checked' : '';
                                ?>
                                <label for="cslfw_fulfill_all">
                                    <input type="checkbox" id="cslfw_fulfill_all" name="cslfw_fulfill_all" <?php echo esc_attr($checked) ?>>
                                    <span><?php esc_html_e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_complete_orders"><?php esc_html_e('Complete order in case of completed status', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_complete_orders" style="vertical-align: top;">
                                <?php
                                $cslfw_complete_orders = get_option('cslfw_complete_orders');
                                $checked = $cslfw_complete_orders ? 'checked' : '';
                                ?>
                                <label for="cslfw_complete_orders">
                                    <input type="checkbox" id="cslfw_complete_orders" name="cslfw_complete_orders" <?php echo esc_attr($checked) ?>>
                                    <span><?php esc_html_e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_box_info_email"><?php esc_html_e('Disable CARGO box info in email', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_box_info_email" style="vertical-align: top;">
                                <?php
                                    $cslfw_box_info = get_option('cslfw_box_info_email');
                                    $checked = $cslfw_box_info ? 'checked' : '';
                                ?>
                                <label for="cslfw_box_info_email">
                                    <input type="checkbox" id="cslfw_box_info_email" name="cslfw_box_info_email" <?php echo esc_attr($checked) ?>>
                                    <span><?php esc_html_e('Disable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_cod_check"><?php esc_html_e('Automatic check for COD', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_cod_check" style="vertical-align: top;">
                                <?php
                                    $cslfw_cod_check = get_option('cslfw_cod_check') ? get_option('cslfw_cod_check') : 'cod';
                                    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
                                ?>
                                <select name="cslfw_cod_check">

                                    <option value="none" <?php if ('none' === $cslfw_cod_check) echo esc_attr('selected="selected"'); ?>><?php esc_html_e('No Automatic COD', 'cargo-shipping-location-for-woocommerce' )?></option>

                                    <?php foreach ( $installed_payment_methods as $key => $value ) : ?>
                                        <option value="<?php echo esc_attr($key) ?>" <?php if ($key === $cslfw_cod_check) echo esc_attr('selected="selected"'); ?>><?php echo $value->title ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_debug_mode"><?php esc_html_e('Enable debug mode', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_debug_mode" style="vertical-align: top;">
                                <?php
                                $cslfw_debug_mode = get_option('cslfw_debug_mode');
                                $checked = $cslfw_debug_mode ? 'checked' : '';
                                ?>
                                <label for="cslfw_debug_mode">
                                    <input type="checkbox" id="cslfw_debug_mode" name="cslfw_debug_mode" <?php echo esc_attr($checked) ?>>
                                    <span><?php esc_html_e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><div style="margin: 10px 0; border-bottom: 1px solid #000"> </div></th>
                    <td><div style="margin: 10px 0; border-bottom: 1px solid #000"> </div></td>

                </tr>
                <tr>
                    <th scope="row" align="left" >
                        <label for="cslfw_shipping_methods_all"><?php esc_html_e('Enable cargo for all shipments', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_shipping_methods_all" style="vertical-align: top;">
                                <?php
                                    $cslfw_shipping_methods_all = get_option('cslfw_shipping_methods_all');
                                    $checked = $cslfw_shipping_methods_all ? 'checked' : '';
                                ?>
                                <label for="cslfw_shipping_methods_all">
                                    <input type="checkbox" id="cslfw_shipping_methods_all" name="cslfw_shipping_methods_all" <?php echo esc_attr($checked) ?>>
                                    <span><?php esc_html_e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr class="cslfw-shipping-wrap" <?php if ($cslfw_shipping_methods_all): ?> style="display: none;" <?php endif; ?>>
                    <th scope="row" align="left" >
                        <label for="cslfw_shipping_methods"><?php esc_html_e('Shipping methods for CARGO', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="cslfw_shipping_methods" style="vertical-align: top;">
                                <?php
                                $shipping_methods = WC()->shipping->get_shipping_methods();
                                $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];

                                foreach ($shipping_methods as $method) :
                                    $checked = in_array($method->id, $cslfw_shiping_methods) ? 'checked' : '';
                                    if ($method->id !== 'cargo-express' && $method->id !== 'woo-baldarp-pickup') :
                                ?>
                                    <label for="cslfw_shipping_methods_<?php echo esc_attr($method->id) ?>" style="display: block">
                                        <input type="checkbox" id="cslfw_shipping_methods_<?php echo esc_attr($method->id) ?>" name="cslfw_shipping_methods[]" value="<?php echo esc_attr($method->id) ?>" <?php echo esc_attr($checked) ?>>
                                        <span><?php echo esc_html($method->method_title) ?></span>
                                    </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </label>
                        </div>
                    </td>
                </tr>

                <tr style="display: none">
					<th scope="row" align="left" >
                        <label for="website_name_cargo"><?php esc_html_e('Bootstrap', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="bootstrap_enable">
                                <span><?php esc_html_e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                <input type="radio"
                                       class="form-control"
                                       name="bootstrap_enalble"
                                       id="bootstrap_enable"
                                       value="1" <?php if (get_option('bootstrap_enalble') == 1) {echo esc_attr("checked"); } ?>>
                            </label>
                            <label for="bootstrap_disable">
                                <span><?php esc_html_e('Disable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                <input type="radio"
                                       class="form-control"
                                       name="bootstrap_enalble"
                                       id="bootstrap_disable"
                                       value="0" <?php if (get_option('bootstrap_enalble') == 0) {echo esc_attr("checked"); } ?>>
                            </label>
						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>
				</tr>

				<tr style="display: none">
                    <th scope="row" align="left" >
                        <label for="disable_order_status"><?php esc_html_e('Disable order status when sent to cargo', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="disable_order_status" style="vertical-align: top;">
                                <input type="checkbox"
                                       placeholder=""
                                       id="disable_order_status"
                                       value="1"
                                       name="disable_order_status"
                                       value="1" <?php if (get_option('disable_order_status')) {echo esc_attr("checked"); } ?> >
                            </label>
                        </div>
                        <div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
                    </td>
                </tr>

                <tr>
					<th scope="row" align="left" >
                        <label for="cargo_order_status"><?php esc_html_e('Order Status:', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_order_status" style="vertical-align: top;">
								<select name="cargo_order_status">
									<option value=""><?php esc_html_e('Default status', 'cargo-shipping-location-for-woocommerce') ?></option>
									<?php
									foreach (wc_get_order_statuses() as $key => $value) {
										$selected = get_option('cargo_order_status') == $key ? 'selected' : '';
										?>
										<option value="<?php echo esc_attr($key) ?>" <?php echo $selected ?>><?php echo esc_html($value) ?></option>
										<?php
									}
									?>
								</select>
							</label>
						</div>
					</td>
				</tr>
			</table>

				<?php wp_nonce_field( 'shippingwoo-settings-save', 'cslfw_shipping_api_settings_fg' ); ?>
				<?php submit_button(); ?>

		</form>
	</div>
</div>
