(function($) {
    $(document).on('click','.js-modal-close',function () {
        $(this).closest('.modal').hide();
    });

    $(document).on('click','.js-cargo-track',function(e){
        e.preventDefault();
        var shippingId = $(this).data('delivery');
        var nonce = $(this).data('nonce');
        $.ajax({
            type: "post",
            url: cargo_obj.ajaxurl,
            data: {
                action:"get_order_tracking_details",
                shipping_id: shippingId,
                _wpnonce: nonce
            },
            success: function(response) {
                console.log(response);
                response = JSON.parse(response);

                if ( !response.errors ) {
                    $('.order-details-ajax .delivery-status').text(response.status_text);
                    $('.order-details-ajax .delivery-status').show();

                } else {

                    let errorMsg = response.message
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
