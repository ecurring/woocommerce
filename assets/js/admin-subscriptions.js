jQuery(window).load(function () {

  function hideOptions ()
  {
    jQuery('#pause-form').removeClass('ecurring-show').addClass('ecurring-hide')
    jQuery('#switch-form').removeClass('ecurring-show').addClass('ecurring-hide')
    jQuery('#cancel-form').removeClass('ecurring-show').addClass('ecurring-hide')
  }

  hideOptions()

  jQuery('#ecurring_subscription_options').on('change', function () {
    var option = jQuery(this).find(':selected').val()
    switch (option) {
      case 'pause':
        hideOptions()
        jQuery('#pause-form').addClass('ecurring-show')
        break
      case 'switch':
        hideOptions()
        jQuery('#switch-form').addClass('ecurring-show')
        break
      case 'cancel':
        hideOptions()
        jQuery('#cancel-form').addClass('ecurring-show')
        break
    }
  })
})


