console.log('reindex script is loaded');
(function($) {
    $(document).on('submit', '.js-reindex-orders', function(e) {
        console.log(ajaxurl);
        e.preventDefault();
        ToggleLoading(true);

        $.ajax({
            type : "post",
            url: ajaxurl,
            data : { action: 'reindex_orders' },
            success: function(response) {
                console.log(response);
                response = JSON.parse(response);
                ToggleLoading(false);

                if ( response.success == true ) {
                    let html = `<p class="notice notice-success">${response.orders_processed} orders were reindexed</p>`
                    $('.js-reindex-orders').append(html);
                    console.log();
                } else {
                    alert('Something went wrong, try again or contact support.')
                }
            },
            error: function(x,h,r) {
                console.log(x);
                console.log(h);
                console.log(r);
            }
        });
    })
})(window.jQuery)
