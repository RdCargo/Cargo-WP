(function($) {
    $(document).on('click','.js-modal-close',function () {
        $(this).closest('.modal').hide();
    });

    $(document).on('click','.js-cargo-track',function(e){
        e.preventDefault();
        var shippingId = jQuery(this).data('delivery');
        $.ajax({
            type: "post",
            url: cargo_obj.ajaxurl,
            data: {action:"get_order_tracking_details", shipping_id : shippingId},
            success: function(response) {
                console.log(response);
                response = JSON.parse(response);

                if ( response.DeliveryStatusText !== '' ) {
                    $('.order-details-ajax .delivery-status').text(response.DeliveryStatusText);
                    $('.order-details-ajax .delivery-status').show();

                }
                if (response.deliveryStatusTime != undefined)
                    $('.order-details-ajax .delivery-status').append(', ' + response.deliveryStatusTime);
                if ( response.errorMsg != '' ) {
                    let errorMsg = response.error_msg ? response.error_msg : response.errorMsg
                    $('.order-details-ajax .delivery-error').text(`${errorMsg}`);
                    $('.order-details-ajax .delivery-error').show();
                }
                $('.order-tracking-model').show();
            },
            error: function(xhr, errorText) {
                alert(errorText);
            }
        });
    })
})(window.jQuery)


console.log('Cargo order script loaded');