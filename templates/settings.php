<?php
$awsb_shipping = new Awsb_Shipping;
?>

<div>
	<h2><?= __('Shipping API Location - Settings', 'astra-woo-cargo') ?></h2>
	<div class="wrap">
		<?php if( isset($_GET['settings-updated']) ) { ?>
			<div id="message" class="updated">
				<p><strong><?php _e('Congratulations settings are saved.', 'astra-woo-cargo') ?></strong></p>
			</div>
		<?php } ?>

		<form method="post" action="options.php" id="seting_cargo">
			<?php settings_fields( 'awsb_shipping_api_settings_fg' ); ?>
			<table>
				<tr class="no-need">
					<th scope="row" align="right" ><label for="shipping_api_username"><?= __('Username: ', 'astra-woo-cargo') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_api_username" style="vertical-align: top;">
                                <input type="text"
                                       id="shipping_api_username"
                                       name="shipping_api_username"
                                       value="<?php echo get_option('shipping_api_username')?>"
                                       placeholder="<?= __('Please Insert Username', 'astra-woo-cargo') ?>"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr class="no-need">
					<th scope="row" align="right" ><label for="shipping_api_pwd"><?= __('Password: ', 'astra-woo-cargo') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_api_pwd" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please Insert Password', 'astra-woo-cargo') ?>"
                                       id="shipping_api_pwd"
                                       name="shipping_api_pwd"
                                       value="<?php echo get_option('shipping_api_pwd') ?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr class="no-need">
					<th scope="row" align="right" ><label for="shipping_api_int1">Int1 : </label></th>
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
                                       value="<?php echo get_option('shipping_api_int1') ?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="shipping_cargo_express"><?= __('Cargo Express: ', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_cargo_express" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please Insert CARGO Express Code', 'astra-woo-cargo')?>"
                                       id="shipping_cargo_express"
                                       name="shipping_cargo_express"
                                       value="<?php echo get_option('shipping_cargo_express') ?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row" align="right" >
                        <label for="shipping_cargo_box"><?= __('Cargo BOX: ', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="shipping_cargo_box" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please Insert CARGO BOX code', 'astra-woo-cargo') ?>"
                                       id="shipping_cargo_box"
                                       name="shipping_cargo_box"
                                       value="<?php echo get_option('shipping_cargo_box')?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>
				
				<tr class="no-need">
					<th scope="row" align="right" >
                        <label for="cargo_consumer_key"><?= __('Consumer Key:', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_consumer_key" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please Insert Consumer Key', 'astra-woo-cargo') ?>"
                                       id="cargo_consumer_key"
                                       name="cargo_consumer_key"
                                       value="<?php echo get_option('cargo_consumer_key')?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr class="no-need">
					<th scope="row" align="right" >
                        <label for="cargo_consumer_secret_key"><?= __('Consumer Secret Key:', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_consumer_secret_key" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please Insert Consumer Secret key', 'astra-woo-cargo') ?>"
                                       id="cargo_consumer_secret_key"
                                       name="cargo_consumer_secret_key"
                                       value="<?php echo get_option('cargo_consumer_secret_key')?>" />
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="from_street"><?= __('From Street Number', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_street" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="Please enter from street number"
                                       id="from_street" name="from_street"
                                       value="<?php echo get_option('from_street')?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="from_street"><?= __('Street Name', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_street_name" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please enter from street Name', 'astra-woo-cargo') ?>"
                                       id="from_street_name"
                                       name="from_street_name"
                                       value="<?php echo get_option('from_street_name')?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="from_city"><?= __('From City', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="from_city" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please enter from City', 'astra-woo-cargo') ?>"
                                       id="from_street_name"
                                       name="from_city"
                                       value="<?php echo get_option('from_city')?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" ><label for="phonenumber_from"><?= __('Phone Number', 'astra-woo-cargo') ?></label></th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="phonenumber_from" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please enter Phone Number', 'astra-woo-cargo') ?>"
                                       id="phonenumber_from"
                                       name="phonenumber_from"
                                       value="<?php echo get_option('phonenumber_from')?>" required autocomplete="off"/>
                            </label>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="website_name_cargo"><?= __('Website name', 'astra-woo-cargo') ?></label>
                    </th>
					<td>
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="website_name_cargo" style="vertical-align: top;">
                                <input type="text"
                                       placeholder="<?= __('Please Enter Your Website Name', 'astra-woo-cargo') ?>"
                                       id="website_name_cargo"
                                       name="website_name_cargo"
                                       value="<?php echo get_option('website_name_cargo')?>" required autocomplete="off"/>
                            </label>
						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>
				</tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="website_name_cargo"><?= __('Bootstrap', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="bootstrap_enalble">
                                <span><?= __('Enable', 'astra-woo-cargo') ?></span>
                                <input type="radio"
                                       class="form-control"
                                       name="bootstrap_enalble"
                                       id="bootstrap_enalble"
                                       value="1" <?php if (get_option('bootstrap_enalble') == 1) {echo "checked"; } ?>>
                            </label>
                            <label for="bootstrap_enalble">
                                <span><?= __('Disable', 'astra-woo-cargo') ?></span>
                                <input type="radio"
                                       class="form-control"
                                       name="bootstrap_enalble"
                                       id="bootstrap_enalble"
                                       value="0" <?php if (get_option('bootstrap_enalble') == 0) {echo "checked"; } ?>>
                            </label>
						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>
				</tr>

				<tr>
                    <th scope="row" align="right" >
                        <label for="send_to_cargo_all"><?= __('Enable for All orders', 'astra-woo-cargo') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="send_to_cargo_all" style="vertical-align: top;">
                                <input type="checkbox"
                                       placeholder=""
                                       id="send_to_cargo_all"
                                       value="1"
                                       name="send_to_cargo_all"
                                       value="1" <?php if(get_option('send_to_cargo_all')) {echo "checked"; } ?> >
                            </label>
                        </div>
                        <div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
                    </td>
                </tr>

				<tr>
                    <th scope="row" align="right" >
                        <label for="send_to_cargo_all"><?= __('Disable order status when sent to cargo', 'astra-woo-cargo') ?></label>
                    </th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">
                            <label for="disable_order_status" style="vertical-align: top;">
                                <input type="checkbox"
                                       placeholder=""
                                       id="disable_order_status"
                                       value="1"
                                       name="disable_order_status"
                                       value="1" <?php if(get_option('disable_order_status')) {echo "checked"; } ?> >
                            </label>
                        </div>
                        <div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
                    </td>
                </tr>

				<tr>
					<th scope="row" align="right" >
                        <label for="cargo_order_status"><?= __('Status Order : Automatic transmission of orders to the shipping company.', 'astra-woo-cargo') ?></label>
                    </th>
					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">
							<label for="cargo_order_status" style="vertical-align: top;">
								<select name="cargo_order_status">
									<option value=""><?= __('Status Selection', 'astra-woo-cargo') ?></option>
									<?php 
									foreach (wc_get_order_statuses() as $key => $value) {
										$selected = get_option('cargo_order_status') == $key ? 'selected' : '';
										?>
										<option value="<?= $key ?>" <?= $selected ?>><?= $value ?></option>
										<?php
									}
									?>
								</select>
							</label>
						</div>
					</td>
				</tr>
			</table>

				<?php wp_nonce_field( 'shippingwoo-settings-save', 'awsb_shipping_api_settings_fg' ); ?>
				<?php submit_button(); ?>	

		</form>
	</div>
	<h3>
        <a href="https://api.cargo.co.il/Webservice/pluginInstruction" target="_blank"><?= __('כיצד להגדיר תוסף CARGO?', 'astra-woo-cargo') ?></a>
    </h3>
</div>