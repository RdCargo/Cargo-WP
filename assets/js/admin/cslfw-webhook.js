(function($) {

    function ajaxAction(data) {
        ToggleLoading(true);

        $.ajax({
            type : "post",
            url: ajaxurl,
            data : data,
            success: function(response) {
                response = JSON.parse(response);
                console.log(response);
                ToggleLoading(false);

                let html = '';
                if ( response.error === false ) {
                    html = `<div class="notice notice-success"><p>${response.message}</p> </div>`;
                } else {
                    html = `<div class="notice notice-error"><p>${response.message}</p> </div>`;
                }
                $('.cslfw-form-notice').empty().append(html);
                // location.reload();
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                console.log('error');
                console.log(textStatus);
            }
        });
    }

    $(document).on('submit', '.cslfw-save-api-key', function(e) {
        e.preventDefault();
        let data = {
            action: 'cslfw_save_cargo_api',
            form_data: $(this).serialize()
        };
        ajaxAction(data)
    })


    $(document).on('click', '.cslfw-add-webhooks', function(e) {
        e.preventDefault();
        let data = {
            action: 'cslfw_add_webhooks'
        };
        ajaxAction(data);
    })


    $(document).on('click', '.cslfw-remove-webhooks', function(e) {
        e.preventDefault();
        let data = {
            action: 'cslfw_delete_webhooks'
        }
        ajaxAction(data)
    })
})(window.jQuery)
