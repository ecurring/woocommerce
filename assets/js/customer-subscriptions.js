jQuery(window).load(function () {

  jQuery("input.tog[type=radio][name*='ecurring']").each(
      function(){
        jQuery(this).on('change', function (){
          const datePicker = jQuery(this).closest("div[class^='ecurring']").find("input[type='date']")
          const specificDateSelected = jQuery(this).val() !== 'specific-date'

          datePicker.toggleClass('ecurring-hide', specificDateSelected)
        })
      }
  );

  function hideOptions (subscription)
  {
    jQuery('div[data-subscription="' + subscription + '"]').
      removeClass('ecurring-show').
      addClass('ecurring-hide')

    jQuery('.resume-update').removeClass('ecurring-show').addClass('ecurring-hide')
  }

  jQuery('.ecurring_subscription_options').on('change', function () {

    var subscription = jQuery(this).data('subscription')
    var option = jQuery(this).find(':selected').val()

    switch (option) {
      case 'pause':
        hideOptions(subscription)
        jQuery(this).parent().find('.pause-form').addClass('ecurring-show')
        break
      case 'resume':
        hideOptions(subscription)
        jQuery(this).parent().find('.resume-update').addClass('ecurring-show')
        break
      case 'switch':
        hideOptions(subscription)
        jQuery(this).parent().find('.switch-form').addClass('ecurring-show')
        break
      case 'cancel':
        hideOptions(subscription)
        jQuery(this).parent().find('.cancel-form').addClass('ecurring-show')
        break
      default:
        jQuery('div[data-subscription]').
          removeClass('ecurring-show').
          addClass('ecurring-hide')
    }
  })

  jQuery('.subscription-options').submit(function (event) {
    event.preventDefault()

    var subscription = jQuery(this).data('subscription')
    var subscription_type = jQuery(this).
      find('.ecurring_subscription_options').
      val()
    var pause_subscription_type = jQuery(this).
      find('input[name=ecurring_pause_subscription]:checked').
      val()
    var pause_resume_date = jQuery(this).
      find('input[name=ecurring_resume_date]').
      val()
    var switch_subscription_type = jQuery(this).
      find('input[name=ecurring_switch_subscription]:checked').
      val()
    var switch_resume_date = jQuery(this).
      find('input[name=ecurring_switch_date]').
      val()
    var ecurring_subscription_plan = jQuery(this).
      find('.ecurring_subscription_plan').
      val()
    var ecurring_cancel_subscription = jQuery(this).
      find('input[name=ecurring_cancel_subscription]').
      val()
    var ecurring_cancel_date = jQuery(this).
      find('input[name=ecurring_cancel_date]').
      val()

    jQuery.post(
      ecurring_customer_subscriptions.ajaxurl,
      {
        'action': 'ecurring_customer_subscriptions',
        'ecurring_subscription_id': subscription,
        'ecurring_subscription_type': subscription_type,
        'ecurring_pause_subscription': pause_subscription_type,
        'ecurring_resume_date': pause_resume_date,
        'ecurring_switch_subscription': switch_subscription_type,
        'ecurring_switch_date': switch_resume_date,
        'ecurring_subscription_plan': ecurring_subscription_plan,
        'ecurring_cancel_subscription': ecurring_cancel_subscription,
        'ecurring_cancel_date': ecurring_cancel_date,
      },
      function () {
        window.location.reload()
      },
    )
  })

})


