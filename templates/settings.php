<?php
$awsb_shipping = new Awsb_Shipping;
?>

<div>
	<h2>Shipping API Location - Settings</h2>
	<div class="wrap">
		<?php if( isset($_GET['settings-updated']) ) { ?>
			<div id="message" class="updated">
				<p><strong><?php _e('Congratulations settings are saved.') ?></strong></p>
			</div>
		<?php } ?>

		<form method="post" action="options.php" id="seting_cargo">

			<?php settings_fields( 'awsb_shipping_api_settings_fg' ); ?>

			<table>

				<tr class="no-need">

					<th scope="row" align="right" ><label for="shipping_api_username">Username : </label></th>

					<td >
						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="shipping_api_username" style="vertical-align: top;"><input type="text" id="shipping_api_username" name="shipping_api_username" value="<?php echo get_option('shipping_api_username')?>" placeholder="Please Insert Username"/></label>

						</div>
					</td>

				</tr>

				<tr class="no-need">

					<th scope="row" align="right" ><label for="shipping_api_pwd">Password : </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="shipping_api_pwd" style="vertical-align: top;"><input type="text" placeholder="Please Insert Password" id="shipping_api_pwd" name="shipping_api_pwd" value="<?php echo get_option('shipping_api_pwd')?>" /></label>

						</div>

					</td>

				</tr>

				<tr class="no-need">

					<th scope="row" align="right" ><label for="shipping_api_int1">Int1 : </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="shipping_api_int1" style="vertical-align: top;"><input type="number" pattern="[0-9]" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" maxlength="6" placeholder="Please Insert Int1" id="shipping_api_int1" name="shipping_api_int1" value="<?php echo get_option('shipping_api_int1')?>" /></label>

						</div>

					</td>

				</tr>
				<tr>

					<th scope="row" align="right" ><label for="shipping_cargo_express">Cargo Express : </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="shipping_cargo_express" style="vertical-align: top;"><input type="text" placeholder="Please Insert CARGO Express Code" id="shipping_cargo_express" name="shipping_cargo_express" value="<?php echo get_option('shipping_cargo_express')?>" required autocomplete="off"/></label>

						</div>

					</td>

				</tr>
				<tr>

					<th scope="row" align="right" ><label for="shipping_cargo_box">Cargo BOX : </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="shipping_cargo_box" style="vertical-align: top;"><input type="text" placeholder="Please Insert CARGO BOX code " id="shipping_cargo_box" name="shipping_cargo_box" value="<?php echo get_option('shipping_cargo_box')?>" required autocomplete="off"/></label>

						</div>

					</td>

				</tr>
				
				<tr class="no-need">

					<th scope="row" align="right" ><label for="cargo_consumer_key"> Consumer Key: </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="cargo_consumer_key" style="vertical-align: top;"><input type="text" placeholder="Please Insert Consumer Key" id="cargo_consumer_key" name="cargo_consumer_key" value="<?php echo get_option('cargo_consumer_key')?>" /></label>

						</div>

					</td>

				</tr>
				<tr class="no-need">

					<th scope="row" align="right" ><label for="cargo_consumer_secret_key"> Consumer Secret Key: </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="cargo_consumer_secret_key" style="vertical-align: top;"><input type="text" placeholder="Please Insert Consumer Secret key" id="cargo_consumer_secret_key" name="cargo_consumer_secret_key" value="<?php echo get_option('cargo_consumer_secret_key')?>" /></label>

						</div>

					</td>

				</tr>
				<!-- <tr class="no-need">

					<th scope="row" align="right" ><label for="cargo_google_api_key"> Google Map API Key: </label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="cargo_google_api_key" style="vertical-align: top;"><input type="text" placeholder="Please Insert google API key" id="cargo_google_api_key" name="cargo_google_api_key" value="<?php echo get_option('cargo_google_api_key')?>" required/></label>

						</div>

					</td>

				</tr> -->
				<tr>

					<th scope="row" align="right" ><label for="from_stree">From Street Number</label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="from_stree" style="vertical-align: top;"><input type="text" placeholder="Please enter from street number" id="from_stree" name="from_stree" value="<?php echo get_option('from_stree')?>" required autocomplete="off"/></label>

						</div>

					</td>

				</tr>
				<tr>

					<th scope="row" align="right" ><label for="from_stree">Street Name</label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="from_stree_name" style="vertical-align: top;"><input type="text" placeholder="Please enter from street Name" id="from_stree_name" name="from_stree_name" value="<?php echo get_option('from_stree_name')?>" required autocomplete="off"/></label>

						</div>

					</td>

				</tr>
				<tr>

					<th scope="row" align="right" ><label for="from_city">From City</label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="from_city" style="vertical-align: top;"><input type="text" placeholder="Please enter from City" id="from_stree_name" name="from_city" value="<?php echo get_option('from_city')?>" required autocomplete="off"/></label>

						</div>

					</td>

				</tr>
				<tr>

					<th scope="row" align="right" ><label for="phonenumber_from">Phone Number</label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="phonenumber_from" style="vertical-align: top;"><input type="text" placeholder="Please enter Phone Number" id="phonenumber_from" name="phonenumber_from" value="<?php echo get_option('phonenumber_from')?>" required autocomplete="off"/></label>

						</div>

					</td>

				</tr>
				<tr>
					<th scope="row" align="right" ><label for="website_name_cargo">Website name</label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="website_name_cargo" style="vertical-align: top;"><input type="text" placeholder="Please Ente Your Website Name" id="website_name_cargo" name="website_name_cargo" value="<?php echo get_option('website_name_cargo')?>" required autocomplete="off"/></label>

						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>

				</tr>
				<tr>
					<th scope="row" align="right" ><label for="website_name_cargo">Bootstrap Enable / Disable </label></th>
					<label for="website_name_cargo" style="vertical-align: top;">It will cause issue if you turn it off</lable>
					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

						<label for="bootstrap_enalble">Enable</label>
						<input type="radio" class="form-control" name="bootstrap_enalble" id="bootstrap_enalble" value="1" <?php if(get_option('bootstrap_enalble') == 1) {echo "checked"; } ?>>
						<input type="radio" class="form-control" name="bootstrap_enalble" id="bootstrap_enalble" value="0" <?php if(get_option('bootstrap_enalble') == 0) {echo "checked"; } ?>>
						<label for="bootstrap_enalble">Disable</label>
						</div>
						<div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
					</td>

				</tr>
				<tr>
                    <th scope="row" align="right" ><label for="send_to_cargo_all">Enable for All orders</label></th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">

                            <label for="send_to_cargo_all" style="vertical-align: top;"><input type="checkbox" placeholder="" id="send_to_cargo_all" value="1" name="send_to_cargo_all" value="1" <?php if(get_option('send_to_cargo_all')) {echo "checked"; } ?> ></label>

                        </div>
                        <div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
                    </td>

                </tr>
				<tr>
                    <th scope="row" align="right" ><label for="send_to_cargo_all">Disable order status when sent to cargo</label></th>
                    <td >
                        <div style="display: inline-block; margin-right: 15px;" class="text">

                            <label for="disable_order_status" style="vertical-align: top;"><input type="checkbox" placeholder="" id="disable_order_status" value="1" name="disable_order_status" value="1" <?php if(get_option('disable_order_status')) {echo "checked"; } ?> ></label>

                        </div>
                        <div class='validation' style='color:red;margin-bottom: 10px; direction:ltr;'></div>
                    </td>

                </tr>
				<tr>

					<th scope="row" align="right" ><label for="cargo_order_status">Status Order : Automatic transmission of orders to the shipping company.</label></th>

					<td >

						<div style="display: inline-block; margin-right: 15px;" class="text">

							<label for="cargo_order_status" style="vertical-align: top;">
								<select name="cargo_order_status">
									<option value="">Status Selection</option>
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
	<h3><a href="https://api.cargo.co.il/Webservice/pluginInstruction" target="_blank">כיצד להגדיר תוסף CARGO?</a></h3>
</div>