(function ($) {
    $(document).ready(function () {

        $('.ecurring-coupon-disabled').on('click','.notice-dismiss',function () {
            var data = {
                action: 'dismiss_coupon_disabled'
            };
            $.post( ajaxurl, data, function( response ) {

            });
        });

        var subscriptionPlan = $('#_woo_ecurring_product_data');

        function hideSalesPrice(subscriptionPlanId) {
            if (subscriptionPlanId !== '0') {
                $('.form-field._sale_price_field ').hide();
            }
            else {
                $('.form-field._sale_price_field ').show();
            }
        }
        hideSalesPrice($(subscriptionPlan).val());

        subscriptionPlan.change(function () {
            hideSalesPrice($(this).val());
        });

        // Show a warning when merchants try to add products to a manual order
        $(".add-order-item").click(function(){
            alert(woo_ecurring_admin_text.manual_order_notice);
        });

        // Remove "Add items " and "Apply coupon" for pending orders with eCurring
        $('.woocommerce-order-data__meta:contains(eCurring)').each(function(i){
            console.log('hit');
            $(".add-line-item").hide();
            $(".add-coupon").hide();
        });

    });
})(jQuery);