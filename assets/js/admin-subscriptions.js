jQuery(window).load(function () {

  jQuery('.ecurring-subscription-cancel').click(function (event) {
    event.preventDefault()

    jQuery.post(
      ajaxurl,
      {
        'action': 'subscription_cancel',
        'subscription_id': jQuery(this).data('ecurring_subscription')
      },
      function (response) {
        console.log(response)
      },
    )
  })

  jQuery('.ecurring-subscription-pause').click(function (event) {
    event.preventDefault()

    jQuery.post(
      ajaxurl,
      {
        'action': 'subscription_pause',
        'subscription_id': jQuery(this).data('ecurring_subscription')
      },
      function (response) {
        console.log(response)
      },
    )
  })

  jQuery('.ecurring-subscription-resume').click(function (event) {
    event.preventDefault()

    jQuery.post(
      ajaxurl,
      {
        'action': 'subscription_resume',
        'subscription_id': jQuery(this).data('ecurring_subscription')
      },
      function (response) {
        console.log(response)
      },
    )
  })



  jQuery('.ecurring-subscription-switch').click(function (event) {
    event.preventDefault()

    jQuery.post(
      ajaxurl,
      {
        'action': 'subscription_switch',
        'subscription_id': jQuery(this).data('ecurring_subscription')
      },
      function (response) {
        console.log(response)
      },
    )
  })



})

