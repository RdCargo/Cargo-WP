(function($) {
	$('select[name="cargo_box_style"]').change(function() {
		if ( $(this).val() === 'cargo_map' ) {
			$('.cslfw-google-maps').show();
		} else {
			$('.cslfw-google-maps').hide();
		}
	})

	$('select[name="cslfw_map_size"]').change(function() {
		if ( $(this).val() === 'map_custom' ) {
			$('.cslfw-map-size').show();
		} else {
			$('.cslfw-map-size').hide();
		}
	})

	$(document).ready(function(){
		$(document).on('click','.send-status',function(e){
			e.preventDefault();
			var orderId = $(this).data('id');
			var cargoDeliveryId = $(this).data('deliveryid');
			var CargoCustomerCode = $(this).data('customercode');
			var orderpage = $(this).data('orderlist');
			var type = $(this).data('type');
			//alert(admin_cargo_obj.ajaxurl);
			if(cargoDeliveryId){
				ToggleLoading(true);
				$.ajax({
					type : "post",
					dataType : "json",
					url : admin_cargo_obj.ajaxurl,
					data : {action: "getOrderStatus", deliveryId : cargoDeliveryId, customerCode : CargoCustomerCode, orderId : orderId,type:type },
					success: function(response) {
						ToggleLoading(false);
						if(response.deliveryStatus != "") {
							alert("סטטוס משלוח  "+response.data);
							if(response.orderStatus != '') {
								$("#statusCargo").val(response.orderStatus).change();
							}
						} else {
						  alert("בעיה לקבל את סטטוס המשלוח");
					   }
					   if(orderpage == 1){
							location.reload();
					   }

					}
				});
			}else{
				alert("ההזמנה עדיין לא נשלחה");
				return false;
			}
		});

		$(document).on('click','.edit-address-cargo',function(e){
			$('.order_data_column_container').children().each(function(){
				if($(this).index() === 2) {
					$(this).find('.edit_address').click();
				}
			});
		});
		$(document).on('click','.submit-cargo-shipping',function(e){
			e.preventDefault();
			let orderID = $(this).data('id');
			let doubleDelivery = $('input[name="cargo_double_delivery"]').is(":checked") ? 2 : 1;
			let shipmentType = $('input[name="cargo_shipment_type"]').length > 0 ? $('input[name="cargo_shipment_type"]').val() : 1;
			let noOfParcel = $('input[name="cargo_packages"]').length > 0 ? $('input[name="cargo_packages"]').val() : 0;

			ToggleLoading(true);
			$.ajax({
				type : "post",
				// dataType : "json",
				url : admin_cargo_obj.ajaxurl,
				data : {
					action: "sendOrderCARGO",
					orderId : orderID,
					double_delivery: doubleDelivery,
					shipment_type: shipmentType,
					no_of_parcel: noOfParcel
				},
				success: function(response) {
					//location.reload();
					console.log(response);
					ToggleLoading(false);
					if(response.shipmentId != "") {
						$(window).scrollTop(0);
						$('#wpbody-content').prepend('<div class="notice removeClass is-dismissible notice-success"><p>הזמנת העברה מוצלחת עבור CARGO</p></div>').delay(500).queue(function(n) {
							$('.removeClass').hide();
							n();
							location.reload();
						});
					} else {
					  alert(response.error_msg);
				   }
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					console.log('error');
					console.log(textStatus);
				}
			});
		});
		$(document).on('click','.label-cargo-shipping',function(e){
			e.preventDefault();
			var shipmentId = $(this).data('id');
			console.log(shipmentId);
			if(shipmentId){
				ToggleLoading(true);
				$.ajax({
					type : "post",
					dataType : "json",
					url : admin_cargo_obj.ajaxurl,
					data : {action: "get_shipment_label", shipmentId : shipmentId},
					success: function(response) {
						console.log(response);
						ToggleLoading(false);
						if(response.pdfLink != "") {
							window.open(response.pdfLink, '_blank');
						}
						else {
						  alert(response.error_msg);
						}
					}
				});
			}else{
				alert("יצירת התווית נכשלה");
				return false;
			}
		});

		$('#website_name_cargo').on('keypress', function (event) {
			//console.log(String.fromCharCode(event.charCode));
			var regex = new RegExp("^[a-zA-Z0-9_\u0590-\u05FF\u200f\u200e \w+]+$");
			var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
			if (!regex.test(key)) {
			   event.preventDefault();
			   $(".validation").html("You can not use "+String.fromCharCode(event.charCode)+" on this field");
			   return false;
			}else{
				$(".validation").html("");
			}
		});
		$('#seting_cargo').submit(function(e) {
			if($.trim($("#website_name_cargo").val()) == "") {
				$(".validation").html("This field is required");
				e.preventDefault(e);
			}
		});
	});
	function ToggleLoading(bool,elem){
		if(bool){
			if(elem != null){
				var odd = 1;
				var set_for = '#'+elem;
			}else{
				var even = 2;
				var set_for = '#wpwrap';
			}
			var hitURL = admin_cargo_obj.path + 'assets/image/Wedges-3s-84px.svg';
			if($('#loader').length == 0){
				$(set_for).append('<div id="loader"><img src='+hitURL+' /></div>');
				if(set_for != 'body'){
					$('#loader').css({"width": "100%", "height": "100%", "background-color": "rgba(204, 204, 204, 0.25)","display":"block","position":"absolute","z-index":"9999","top":"0px"});
					$('#loader img').css({"top": "50%","width": "5%","text-align": "center","left": "47%","position": "fixed","z-index":"9999"});
				}else{
					$('#loader').css({"width": "100%", "height": "100%", "background-color": "rgba(204, 204, 204, 0.25)","display":"block","position":"absolute","z-index":"9999","top":"0px"});
					$('#loader img').css({"top": "50%","width": "5%","text-align": "center","left": "47%","position": "fixed","z-index":"9999"});
				}
			}
		}else{
			$('#loader').remove();
		}
	}
})(window.jQuery)