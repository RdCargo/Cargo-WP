(function($) {
    $(document).ready(function() {
        let data = {
            action: 'cslfw_get_bulk_action_progress'
        }

        let bulkActionsInterval = setInterval(function() {
            $.ajax({
                type: "post",
                url : admin_cargo_obj.ajaxurl,
                dataType: "json",
                data: data,
                success: function(response) {
                    if (response.progress) {
                        console.log('progress.response', response);
                        $(`#${response.action}`)?.html(response.progress_html)

                        if (response.action === 'cslfw_shipments_label_process' && response.completed) {
                            window.open(response.label_link, '_blank')
                        }
                        if (response.action === 'cslfw_bulk_shipment_progress') {
                            response.progress.forEach(order => {
                                if (order.status.includes('ShipmentID: ')) {
                                    let key = `post-${order.orderId}`
                                    let hposKey = `order-${order.orderId}`
                                    let shipmentId = order.status.replace('ShipmentID: ', '');
                                    if ($(`tr#${key} td.cslfw_delivery_status .cslfw-status, tr#${hposKey} td.cslfw_delivery_status .cslfw-status`).length === 0) {
                                        let content = `<p class="cslfw-status status-1">${shipmentId} - Open</p>`

                                        $(`tr#${key} td.cslfw_delivery_status`).append(content)
                                        $(`tr#${hposKey} td.cslfw_delivery_status`).append(content)
                                        $(`tr#${key} td.send_to_cargo`).html('')
                                        $(`tr#${hposKey} td.send_to_cargo`).html('')
                                    }
                                }
                            })
                        }
                    }
                    if (response.completed) {
                        clearInterval(bulkActionsInterval);
                    }
                },
                error: function( jqXHR, textStatus, errorThrown ) {
                    clearInterval(bulkActionsInterval)
                    console.log('error', errorThrown);
                    console.log(textStatus);
                }
            });
        }, 500)
    })
})(window.jQuery)
