(function($) {
    $(document).on('submit', '.cslfw-send-email', function(e) {
        console.log(ajaxurl);
        e.preventDefault();
        ToggleLoading(true);

        $.ajax({
            type : "post",
            url: ajaxurl,
            data : {
                action: 'cslfw_send_email',
                form_data: $(this).serialize(),
            },
            success: function(response) {
                console.log(response);
                response = JSON.parse(response);
                ToggleLoading(false);

                console.log(response);
                $('.cslfw-send-email .notice').remove();
                let html = '';
                if ( response.error === 'false' ) {
                    html = `<div class="notice notice-success"><p>${response.message}</p> </div>`;
                } else {
                    html = `<div class="notice notice-success"><p>${response.message}</p> </div>`;
                }

                $('.cslfw-send-email').append(html);

            }
        });
    })
})(window.jQuery)
