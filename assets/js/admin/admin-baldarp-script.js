jQuery(document).ready(function(){
	jQuery(document).on('click','.send-status',function(){
		var orderId = jQuery(this).data('id');
		var cargoDeliveryId = jQuery(this).data('deliveryid');
		var CargoCustomerCode = jQuery(this).data('customercode');
		var orderpage = jQuery(this).data('orderlist');
		var type = jQuery(this).data('type');
		//alert(admin_cargo_obj.ajaxurl);
		if(cargoDeliveryId){
			ToggleLoading(true);
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : admin_cargo_obj.ajaxurl,
				data : {action: "getOrderStatus", deliveryId : cargoDeliveryId, customerCode : CargoCustomerCode, orderId : orderId,type:type },
				success: function(response) {
					ToggleLoading(false);
				   	if(response.deliveryStatus != "") {
				      	alert("סטטוס משלוח  "+response.data);
				      	if(response.orderStatus != '') {
				      		jQuery("#statusCargo").val(response.orderStatus).change();
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

	jQuery(document).on('click','.edit-address-cargo',function(e){
		jQuery('.order_data_column_container').children().each(function(){
			if(jQuery(this).index() === 2) {
				jQuery(this).find('.edit_address').click();
			}
		});
	});
	jQuery(document).on('click','.submit-cargo-shipping',function(e){
		e.preventDefault();
		let orderID = jQuery(this).data('id');
		let doubleDelivery = jQuery('input[name="cargo_double_delivery"]').is(":checked") ? 2 : 1;
		let shipmentType = jQuery('input[name="cargo_shipment_type"]:checked').val();
		let noOfParcel = jQuery('input[name="cargo_shipment_type"]:checked').val();
		console.log(doubleDelivery);
		console.log(shipmentType);
		ToggleLoading(true);
		jQuery.ajax({
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
					jQuery(window).scrollTop(0);
					jQuery('#wpbody-content').prepend('<div class="notice removeClass is-dismissible notice-success"><p>הזמנת העברה מוצלחת עבור CARGO</p></div>').delay(500).queue(function(n) {
						jQuery('.removeClass').hide();
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
	jQuery(document).on('click','.label-cargo-shipping',function(e){
		e.preventDefault();
		var shipmentId = jQuery(this).data('id');
		if(shipmentId){
			ToggleLoading(true);
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : admin_cargo_obj.ajaxurl,
				data : {action: "get_shipment_label", shipmentId : shipmentId},
				success: function(response) {
					ToggleLoading(false);
				   	if(response.pdfLink != "") {
						window.open(response.pdfLink, '_blank');
				   	}
				   	else {
				      alert(response.error_msg);
				   	}
				   if(orderpage == 1){
				   		location.reload();
				   }

				}
		    });
		}else{
			alert("יצירת התווית נכשלה");
			return false;
		}
	});

	jQuery('#website_name_cargo').on('keypress', function (event) {
		//console.log(String.fromCharCode(event.charCode));
		var regex = new RegExp("^[a-zA-Z0-9_\u0590-\u05FF\u200f\u200e \w+]+$");
		var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
		if (!regex.test(key)) {
		   event.preventDefault();
		   jQuery(".validation").html("You can not use "+String.fromCharCode(event.charCode)+" on this field");
		   return false;
		}else{
			jQuery(".validation").html("");
		}
	});
	jQuery('#seting_cargo').submit(function(e) {
		if(jQuery.trim(jQuery("#website_name_cargo").val()) == "") {
			jQuery(".validation").html("This field is required");
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
		if(jQuery('#loader').length == 0){
			jQuery(set_for).append('<div id="loader"><img src='+hitURL+' /></div>');
			if(set_for != 'body'){
				jQuery('#loader').css({"width": "100%", "height": "100%", "background-color": "rgba(204, 204, 204, 0.25)","display":"block","position":"absolute","z-index":"9999","top":"0px"});
				jQuery('#loader img').css({"top": "50%","width": "5%","text-align": "center","left": "47%","position": "fixed","z-index":"9999"});
			}else{
				jQuery('#loader').css({"width": "100%", "height": "100%", "background-color": "rgba(204, 204, 204, 0.25)","display":"block","position":"absolute","z-index":"9999","top":"0px"});
				jQuery('#loader img').css({"top": "50%","width": "5%","text-align": "center","left": "47%","position": "fixed","z-index":"9999"});
			}
		}
	}else{
		jQuery('#loader').remove();	 
	}
}