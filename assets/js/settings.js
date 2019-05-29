jQuery(function($) {
    $('#woo-ecurring_test_mode_enabled').change(function() {
        if ($(this).is(':checked'))
        {
            $('#woo-ecurring_test_api_key').attr('required', true).closest('tr').show();
        }
        else
        {
            $('#woo-ecurring_test_api_key').removeAttr('required').closest('tr').hide();
        }
    }).change();
});
