<?php
$cslfw_shipping = new CSLFW_Shipping;
?>

<div>
	<h2><?php _e('Shipping API Location - Settings', 'cargo-shipping-location-for-woocommerce') ?></h2>
	<div class="wrap">
		<?php if( isset($_GET['settings-updated']) ) { ?>
			<div id="message" class="updated">
				<p><strong><?php _e('Congratulations settings are saved.', 'cargo-shipping-location-for-woocommerce') ?></strong></p>
			</div>
		<?php } ?>

		<form method="post" action="options.php" id="seting_cargo">
			<?php settings_fields( 'cslfw_shipping_api_settings_fg' ); ?>
			<table>
				<tr class="no-need">
					<th scope="row" align="left" ><label for="shipping_api_username"><?php _e('Username: ', 'cargo-shipping-location-for-woocommerce') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_api_username" style="vertical-align: top;">
                                <input type="text"
                                       id="shipping_api_username"
                                       name="shipping_api_username"
                                       value="<?php echo esc_attr( get_option('shipping_api_username') )?>"
                                       placeholder="<?php _e('Please Insert Username', 'cargo-shipping-location-for-woocommerce') ?>"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr class="no-need">
					<th scope="row" align="left" ><label for="shipping_api_pwd"><?php _e('Password: ', 'cargo-shipping-location-for-woocommerce') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_api_pwd" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please Insert Password', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="shipping_api_pwd"
                                       name="shipping_api_pwd"
                                       value="<?php echo esc_attr( get_option('shipping_api_pwd') ) ?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr class="no-need">
					<th scope="row" align="left" ><label for="shipping_api_int1">Int1 : </label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_api_int1" style="vertical-align: top;">
                                <input type="number"
                                       pattern="[0-9]"
                                       oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                       maxlength="6"
                                       placeholder="Please Insert Int1"
                                       id="shipping_api_int1"
                                       name="shipping_api_int1"
                                       value="<?php echo esc_attr( get_option('shipping_api_int1') ) ?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="shipping_cargo_express"><?php _e('Cargo Express: ', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_cargo_express" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please Insert CARGO Express Code', 'cargo-shipping-location-for-woocommerce')?>"
                                       id="shipping_cargo_express"
                                       name="shipping_cargo_express"
                                       value="<?php echo esc_attr( get_option('shipping_cargo_express') ) ?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row" align="left" >
                        <label for="shipping_cargo_box"><?php _e('Cargo BOX: ', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_cargo_box" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please Insert CARGO BOX code', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="shipping_cargo_box"
                                       name="shipping_cargo_box"
                                       value="<?php echo esc_attr( get_option('shipping_cargo_box') ) ?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

                <tr>
                    <th scope="row" align="left" >
                        <label for="cargo_box_style"><?php _e('Cargo Box Checkout Style', 'cargo-shipping-location-for-woocommerce') ?></label>
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
                                    <option value="<?php echo $key ?>" <?php if ($key === $cargo_box_style) echo esc_attr('selected="selected"'); ?>><?php echo $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <p style="margin-top: 0;">Automatic choice will choose closest pickup point automatically from customer address.</p>
                        </div>
                    </td>
                </tr>
				
				<tr class="no-need">
					<th scope="row" align="left" >
                        <label for="cargo_consumer_key"><?php _e('Consumer Key:', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_consumer_key" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please Insert Consumer Key', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="cargo_consumer_key"
                                       name="cargo_consumer_key"
                                       value="<?php echo esc_attr( get_option('cargo_consumer_key') )?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr class="no-need">
					<th scope="row" align="left" >
                        <label for="cargo_consumer_secret_key"><?php _e('Consumer Secret Key:', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_consumer_secret_key" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please Insert Consumer Secret key', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="cargo_consumer_secret_key"
                                       name="cargo_consumer_secret_key"
                                       value="<?php echo esc_attr( get_option('cargo_consumer_secret_key') ) ?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="from_street"><?php _e('From Street Number', 'cargo-shipping-location-for-woocommerce') ?></label>
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
                        <label for="from_street"><?php _e('Street Name', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_street_name" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please enter from street Name', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="from_street_name"
                                       name="from_street_name"
                                       value="<?php echo esc_attr( get_option('from_street_name') ) ?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="from_city"><?php _e('From City', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_city" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please enter from City', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="from_street_name"
                                       name="from_city"
                                       value="<?php echo esc_attr( get_option('from_city') )?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" ><label for="phonenumber_from"><?php _e('Phone Number', 'cargo-shipping-location-for-woocommerce') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="phonenumber_from" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please enter Phone Number', 'cargo-shipping-location-for-woocommerce') ?>"
                                       id="phonenumber_from"
                                       name="phonenumber_from"
                                       value="<?php echo esc_attr( get_option('phonenumber_from') )?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="left" >
                        <label for="website_name_cargo"><?php _e('Website name', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td>
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="website_name_cargo" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?php _e('Please Enter Your Website Name', 'cargo-shipping-location-for-woocommerce') ?>"
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
                        <label for="website_name_cargo"><?php _e('Bootstrap', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="bootstrap_enalble">
                                <span><?php _e('Enable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                <input type="radio"
                                       class="form-control"
                                       name="bootstrap_enalble"
                                       id="bootstrap_enalble"
                                       value="1" <?php if (get_option('bootstrap_enalble') == 1) {echo esc_attr("checked"); } ?>>
                            </label>
                            <label for="bootstrap_enalble">
                                <span><?php _e('Disable', 'cargo-shipping-location-for-woocommerce') ?></span>
                                <input type="radio"
                                       class="form-control"
                                       name="bootstrap_enalble"
                                       id="bootstrap_enalble"
                                       value="0" <?php if (get_option('bootstrap_enalble') == 0) {echo esc_attr("checked"); } ?>>
                            </label>
						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>
				</tr>

				<tr>
                    <th scope="row" align="left" >
                        <label for="send_to_cargo_all"><?php _e('Enable for All orders', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="send_to_cargo_all" style="vertical-align: top;">
                                <input type="checkbox"
                                       placeholder=""
                                       id="send_to_cargo_all"
                                       value="1"
                                       name="send_to_cargo_all"
                                       value="1" <?php if( get_option('send_to_cargo_all')) {echo esc_attr("checked"); } ?> >
                            </label>
                        </div>
                        <div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
                    </td>
                </tr>

				<tr>
                    <th scope="row" align="left" >
                        <label for="send_to_cargo_all"><?php _e('Disable order status when sent to cargo', 'cargo-shipping-location-for-woocommerce') ?></label>
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
                        <label for="cargo_order_status"><?php _e('Order Status:', 'cargo-shipping-location-for-woocommerce') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_order_status" style="vertical-align: top;">
								<select name="cargo_order_status">
									<option value=""><?php _e('Status Selection', 'cargo-shipping-location-for-woocommerce') ?></option>
									<?php 
									foreach (wc_get_order_statuses() as $key => $value) {
										$selected = get_option('cargo_order_status') == $key ? 'selected' : '';
										?>
										<option value="<?php echo $key ?>" <?php echo $selected ?>><?php echo $value ?></option>
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
	<h3>
        <a href="https://api.cargo.co.il/Webservice/pluginInstruction" target="_blank"><?php _e('כיצד להגדיר תוסף CARGO?', 'cargo-shipping-location-for-woocommerce') ?></a>
    </h3>
</div>