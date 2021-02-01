function canceleCurringSubscriptionWithID(e) {
    var subscriptionId = e.getAttribute("data-ecurring-subscription-id");

    Swal.fire({
        title: woo_ecurring_ajax.are_you_sure_short,
        text: woo_ecurring_ajax.are_you_sure_long,
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: woo_ecurring_ajax.yes_cancel_it,
        cancelButtonText: woo_ecurring_ajax.no_dont_cancel_it,
    }).then((result) => {
        if (result.value) {

            var data = {
                action: 'ecurring_my_account_cancel_subscription',
                subscription_id: subscriptionId
            };

            jQuery.ajax(
                {
                    type: "post",
                    dataType: "json",
                    url: woo_ecurring_ajax.ajax_url,
                    data: data,
                    success: function (msg) {

                        if (msg.result === 'success') {

                            document.getElementById('ecurring-status-subscription-' + subscriptionId).innerHTML = woo_ecurring_ajax.cancelled;
                            document.getElementById('ecurring_cancel_subscription_' + subscriptionId).style.visibility = 'hidden';

                            Swal.fire(
                                woo_ecurring_ajax.cancelled + '!',
                                woo_ecurring_ajax.your_subscription + subscriptionId + woo_ecurring_ajax.is_cancelled,
                                'success'
                            )
                        }

                        if (msg.result === 'failed') {
                            Swal.fire(
                                woo_ecurring_ajax.cancel_failed + '!',
                                woo_ecurring_ajax.your_subscription + subscriptionId + woo_ecurring_ajax.is_not_cancelled,
                                'error'
                            )
                        }

                    }
                });
        }
    })

}

(function ($) {

    $(document).on('ajaxComplete', function () {
        if ($('.ecurring-mandate-accept').length > 0) {
            $('#place_order').prop('disabled', true);

            $('#mandate_accepted').on('click', function () {
                if (this.checked) {
                    $('#place_order').prop('disabled', false);
                }
                else {
                    $('#place_order').prop('disabled', true);
                }
            });
        }
    });

    $(document).on('click','.add_to_cart_button',function () {

        var productId = $(this).data('product_id'),
            data = {
                action: 'ecurring_add_to_cart_redirect',
                product_id: productId
            };

        $.post( woo_ecurring_ajax.ajax_url, data, function( response ) {

            if (response.result === 'success') {
                if (response.is_ajax == 'yes') {
                    $(document).on( 'added_to_cart', function() {
                        window.location = response.url;
                    });
                }
            }
        });
    })

})(jQuery);
