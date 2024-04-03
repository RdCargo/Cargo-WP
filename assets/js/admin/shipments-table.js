(function($) {
    function ajaxAction(data) {
        ToggleLoading(true);

        $.ajax({
            type : "post",
            url : admin_shipments_obj.ajaxurl,
            data : data,
            success: function(response) {
                response = JSON.parse(response);
                console.log(response);
                ToggleLoading(false);

                let html = '';
                if ( response.error === false || response.type === 'success') {

                    html = `<div class="notice notice-success"><p>${response.message ?? response.data}</p> </div>`;
                    setTimeout(() => {
                        location.reload()
                    }, 1000 )
                } else {
                    html = `<div class="notice notice-error"><p>${response.message ?? response.data}</p> </div>`;
                }
                $('.cslfw-form-notice').empty().append(html);
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                console.log('error');
                console.log(textStatus);
            }
        });
    }

    $(document).on('click','.js-check-shipment-status',function(e){
        e.preventDefault();

        let shipmentId = $(this).data('shipment-id');
        let orderId = $(this).data('order-id');
        let nonce = $(this).data('nonce');

        let data = {
            action: 'getOrderStatus',
            orderId: orderId,
            deliveryId: shipmentId,
            _wpnonce: nonce,
        };
        ajaxAction(data)
    });

    $(document).on('submit', '.js-shipments-table-actions', function(e) {

        let formDataObject = {};
        $(this).serializeArray().forEach(obj => {
            formDataObject[obj.name] = obj.value;
        });
        e.preventDefault();

        if (formDataObject.cslfw_action !== '-1') {
            if (formDataObject.cslfw_action == 'get_multiple_shipment_labels') {
                $('#print-labels').removeClass('hidden');
            } else {
                let data = {
                    action: formDataObject.cslfw_action,
                    form_data: $(this).serialize(),
                    shipments: $('.js-cargo-shipments-data').serialize(),
                };

                console.log(data);
                ToggleLoading(true);
                $.ajax({
                    type : "post",
                    dataType : "json",
                    url : admin_shipments_obj.ajaxurl,
                    data : data,
                    success: function(response) {
                        console.log(response);
                        ToggleLoading(false);
                        if(response.pdfLink !== "") {
                            window.open(response.pdfLink, '_blank');
                        } else {
                            alert(response.error_msg);
                        }
                    }
                });
            }
        }
    })

    $(document).on('click','.js-print-shipment-label',function(e){
        e.preventDefault();

        let shipmentId = $(this).data('shipment-id');
        let orderId = $(this).data('order-id');
        let nonce = $(this).data('nonce');

        console.log({
            action: "get_shipment_label",
            shipmentId : shipmentId,
            orderId: orderId,
            _wpnonce: nonce
        })
        if (orderId){
            ToggleLoading(true);
            $.ajax({
                type : "post",
                dataType : "json",
                url : admin_shipments_obj.ajaxurl,
                data : {
                    action: "get_shipment_label",
                    shipmentId : shipmentId,
                    orderId: orderId,
                    _wpnonce: nonce
                },
                success: function(response) {
                    console.log(response);
                    ToggleLoading(false);
                    if(response.pdfLink !== "") {
                        window.open(response.pdfLink, '_blank');
                    } else {
                        alert(response.error_msg);
                    }
                }
            });
        } else {
            alert("יצירת התווית נכשלה");
            return false;
        }
    });

    $(document).on('change', 'input[name="printType"]', function() {
        if ($(this).val() === 'A4') {
            $('.js-a4-option').removeClass('hidden');
        } else {
            $('.js-a4-option').addClass('hidden');
        }
    })

    $(document).on('click', '.js-modal-close', function(e) {
        e.preventDefault();

        $(this).closest('.cslfw_modal').addClass('hidden')
    })

    $(document).on('submit', '.js-print-multiple-labels', function(e) {
        e.preventDefault();

        let data = {
            action: 'get_multiple_shipment_labels',
            form_data: $(this).serialize(),
            shipments: $('.js-cargo-shipments-data').serialize(),
        };

        console.log(data);
        ToggleLoading(true);
        $.ajax({
            type : "post",
            dataType : "json",
            url : admin_shipments_obj.ajaxurl,
            data : data,
            success: function(response) {
                console.log(response);
                ToggleLoading(false);
                if(response.pdfLink !== "") {
                    window.open(response.pdfLink, '_blank');
                } else {
                    alert(response.error_msg);
                }
            }
        });
    })
})(window.jQuery)
