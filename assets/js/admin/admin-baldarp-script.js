(function($) {
	$('#seting_cargo').on('submit', function(e) {
		if ( $(this).find('#shipping_cargo_express').val().length < 1 && $(this).find('#shipping_cargo_box').val().length < 1 ) {
			e.preventDefault();
			alert('Fill in cargo box or cargo express to proceed');
		}
	})

	$('select[name="cargo_box_style"]').change(function() {
		if ( $(this).val() === 'cargo_map' ) {
			$('.cslfw-google-maps').show();
			$('#cslfw_google_api_key').prop('required', true)
		} else {
			$('.cslfw-google-maps').hide();
			$('#cslfw_google_api_key').prop('required', false)

		}
	})

    $('input#cslfw_shipping_methods_all').change(function() {
        if ( $(this).is(':checked') ) {
            $('.cslfw-shipping-wrap').hide();
        } else {
            $('.cslfw-shipping-wrap').show();
        }
    })
	$('input[name="cargo_cod"]').change(function() {
		if ( $(this).is(':checked') ) {
			$('.cargo_cod_type').show();
		} else {
			$('.cargo_cod_type').hide();
			$('input[name="cargo_cod_type"]').prop('checked', false);
		}
	})
	$('select[name="cslfw_map_size"]').change(function() {
		if ( $(this).val() === 'map_custom' ) {
			$('.cslfw-map-size').show();
		} else {
			$('.cslfw-map-size').hide();
		}
	})

    $(document).on('change', '#cargo_city',function() {
        let data = {
            city: $(this).val()
        }
        ToggleLoading(true);
        $.ajax({
            type: "post",
            url: "https://api.cargo.co.il/Webservice/getPickUpPoints",
            data: JSON.stringify(data),
            success: function(response) {
                //location.reload();
                console.log(response);
                let html = '';

                if ( response.PointsDetails.length > 0 ) {
                    response.PointsDetails.forEach( (point) => {
                        html += `<option value="${point.DistributionPointID}">${point.DistributionPointName}, ${point.CityName}, ${point.StreetName} ${point.StreetNum}</option>`;
                    })
                    $('#cargo_pickup_point').html(html).show();

                } else {
                    $('#cargo_pickup_point').hide();

                    alert('No points found by this city');
                }
                ToggleLoading(false);
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                console.log('error');
                console.log(textStatus);
            }
        });
    })

	$(document).ready(function() {
		$(document).on('click','.send-status',function(e){
			e.preventDefault();
			var orderId = $(this).data('id');
			var cargoDeliveryId = $(this).data('deliveryid');
			var type = $(this).data('type');
			//alert(admin_cargo_obj.ajaxurl);
            ToggleLoading(true);
            $.ajax({
                type : "post",
                dataType : "json",
                url : admin_cargo_obj.ajaxurl,
                data : {action: "getOrderStatus", orderId: orderId, deliveryId: cargoDeliveryId },
                success: function(response) {
                    console.log(response);
                    ToggleLoading(false);
                    if(response.deliveryStatus != "") {
                        alert("סטטוס משלוח  "+response.data);
                        location.reload();
                    } else {
                      alert("בעיה לקבל את סטטוס המשלוח");
                   }
                }
            });
		});

		$(document).on('click','.submit-cargo-shipping:not([disabled])',function(e){
			e.preventDefault();

			$(this).attr('disabled', true);

			let data = {
				action: "sendOrderCARGO",
				orderId : $(this).data('id'),
				double_delivery: $('input[name="cargo_double_delivery"]').is(":checked") ? 2 : 1,
				shipment_type: $('input[name="cargo_shipment_type"]').length > 0 ? $('input[name="cargo_shipment_type"]:checked').val() : 1,
				no_of_parcel: $('input[name="cargo_packages"]').length > 0 ? $('input[name="cargo_packages"]').val() : 0,
				cargo_cod: $('input[name="cargo_cod"]').length > 0 ? $('input[name="cargo_cod"]').is(':checked') ? 1 : 0 : 0,
                fulfillment: $('input[name="cslfw_fulfillment"]').length > 0 ? $('input[name="cslfw_fulfillment"]').is(':checked') ? 1 : 0 : 0,
				cargo_cod_type: $('input[name="cargo_cod_type"]').length > 0 ? $('input[name="cargo_cod_type"]').is(':checked') ? 1 : '' : ''
			};


			if ( $('#cargo_pickup_point').length > 0 &&  $('#cargo_pickup_point option:selected').attr('value') !== '' ) data['box_point_id'] = $('#cargo_pickup_point option:selected').attr('value');
			if ( $(this).attr('data-box-point-id') ) data['box_point_id'] = $(this).attr('data-box-point-id');
			console.log(data);

			ToggleLoading(true);
			$.ajax({
				type : "post",
				// dataType : "json",
				url : admin_cargo_obj.ajaxurl,
				data : data,
				success: function(response) {
					//location.reload();
					console.log(response);
					ToggleLoading(false);
                    $(this).attr('disabled', false);

                    if( response.shipmentId != "" ) {
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
			var orderId = $(this).data('order-id');
			console.log(shipmentId);
			if(orderId){
				ToggleLoading(true);
				$.ajax({
					type : "post",
					dataType : "json",
					url : admin_cargo_obj.ajaxurl,
					data : {action: "get_shipment_label", shipmentId : shipmentId, orderId: orderId},
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
        if ( bool ) {
            const set_for = elem !== null ? '#wpwrap' : `#${elem}`;
            if($('#loader').length == 0){
                $(set_for).append(`<div id="loader"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; background: none; display: block; shape-rendering: auto;" width="84px" height="84px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="translate(50 50)"> <g transform="scale(0.7)"> <g transform="translate(-50 -50)"> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="0.7575757575757576s"></animateTransform> <path fill-opacity="0.8" fill="#e15b64" d="M50 50L50 0A50 50 0 0 1 100 50Z"></path> </g> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="1.0101010101010102s"></animateTransform> <path fill-opacity="0.8" fill="#f47e60" d="M50 50L50 0A50 50 0 0 1 100 50Z" transform="rotate(90 50 50)"></path> </g> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="1.5151515151515151s"></animateTransform> <path fill-opacity="0.8" fill="#f8b26a" d="M50 50L50 0A50 50 0 0 1 100 50Z" transform="rotate(180 50 50)"></path> </g> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="3.0303030303030303s"></animateTransform> <path fill-opacity="0.8" fill="#abbd81" d="M50 50L50 0A50 50 0 0 1 100 50Z" transform="rotate(270 50 50)"></path> </g> </g> </g></g></div>`);
                $('#loader').css({
                    "width": "100%",
                    "height": "100%",
                    "background-color": "rgba(204, 204, 204, 0.25)",
                    "display":"block",
                    "position":"absolute",
                    "z-index":"9999",
                    "top":"0px"
                });
                $('#loader svg').css({
                    "top": "50%",
                    "width": "5%",
                    "text-align": "center",
                    "left": "47%",
                    "position": "fixed",
                    "z-index":"9999"
                });
            }
        } else {
            $('#loader').remove();
        }
    }

	$('.cslfw-create-new-shipment').click(function(e) {
		e.preventDefault();
		$(this).hide();
		$('.cargo-submit-form-wrap').show();
	})
})(window.jQuery)
