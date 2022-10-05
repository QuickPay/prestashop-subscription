$(document).ready(function () {
  $(document).on('change', 'input[name=quickpaysubscription_cart_type]', function () {
    let _this = $(this)
    let subscriptionProduct = $('input[name="quickpaysubscription_subscription_product"]');
    let subscriptionCartId = $('input[name="quickpaysubscription_subscription_cart_id"]');
    if (parseInt(_this.val()) === 2) {
      $('.subscription_plans').addClass('active')
    } else {
      $('.subscription_plans').removeClass('active')

      let subscribeProductCartId = subscriptionCartId.length ? parseInt(subscriptionCartId.val()) : 0
      let subscribeProductProductId = subscriptionProduct.length ? parseInt(subscriptionProduct.val()) : 0

      if (subscribeProductCartId && subscribeProductProductId) {
        $.ajax({
          url: $('input[name=quickpaysubscription_ajax_url]').val(),
          cache: false,
          async: false,
          type: 'POST',
          data: {
            ajax: true,
            action: 'delete',
            token: $('input[name=quickpaysubscription_token]').val(),
            id_subscription_product: subscribeProductProductId,
            id_subscription_cart: subscribeProductCartId,
          },
          success: function (response) {
            if (response['status']) {
              $('#quickpaysubscription_cart_type_purchase').click()
            }
          }
        })
      }
    }
  })

  $(document).on('change', 'select[name=quickpaysubscription_selected_plan_frequency]', function () {
    let subscriptionProduct = $('input[name="quickpaysubscription_subscription_product"]');
    let subscriptionCartId = $('input[name="quickpaysubscription_subscription_cart_id"]');
    let subscribeProductCartId = subscriptionCartId.length ? parseInt(subscriptionCartId.val()) : 0
    let subscribeProductProductId = subscriptionProduct.length ? parseInt(subscriptionProduct.val()) : 0

    if (subscribeProductCartId && subscribeProductProductId) {
      let subscribeSelectedPlanFrequency = $('select[name=quickpaysubscription_selected_plan_frequency]')

      $.ajax({
        url: $('input[name=quickpaysubscription_ajax_url]').val(),
        cache: false,
        async: false,
        type: 'POST',
        data: {
          ajax: true,
          action: 'update',
          token: $('input[name=quickpaysubscription_token]').val(),
          id_subscription_product: subscribeProductProductId,
          id_subscription_cart: subscribeProductCartId,
          id_plan: subscribeSelectedPlanFrequency.find(':selected').data('id_plan'),
          frequency: subscribeSelectedPlanFrequency.val(),
        },
        success: function (response) {
          if (response['status']) {
            subscribeSelectedPlanFrequency.find('option[value=' + response['cycle'] + '][data-id_plan=' + response['plan'] + ']')
          }
        }
      })
    }
  })

  prestashop.on(
    'updatedProduct',
    function (e) {
      $.ajax({
        url: $('input[name=quickpaysubscription_ajax_url]').val(),
        cache: false,
        async: false,
        type: 'POST',
        data: {
          ajax: true,
          action: 'checkInCart',
          token: $('input[name=quickpaysubscription_token]').val(),
          id_product: $('input[name=id_product]').val(),
          id_product_attribute: e.id_product_attribute,
          id_customization: $('input[name=id_customization]').val(),
        },
        success: function (response) {
          response = JSON.parse(response)
          if (response['subscribeProduct'] != null) {
            $('input[name="quickpaysubscription_subscription_cart_id"]').val(response['subscribeProduct']['id'])
            $('input[name="quickpaysubscription_subscription_product"]').val(response['subscribeProduct']['id_subscription_product'])
            $('#quickpaysubscription_cart_type_subscribe').click()
            $('select[name=quickpaysubscription_selected_plan_frequency]').find('option[value=' + response['subscribeProduct']['frequency'] + '][data-id_plan=' + response['subscribeProduct']['id_plan'] + ']')
          } else {
            $('input[name="quickpaysubscription_subscription_cart_id"]').val(0)
            $('input[name="quickpaysubscription_subscription_product"]').val(0)
            $('#quickpaysubscription_cart_type_purchase').click()
          }
        }
      })
    }
  )

  prestashop.on(
    'updateCart',
    function(e) {
      if (typeof e.resp == 'undefined') {
        return
      }

      if (parseInt($('input[name=quickpaysubscription_cart_type]:checked').val()) === 1) {
        return
      }

      let resp = e.resp

      if (!resp.success) {
        return
      }

      if ($('body').prop('id') !== 'product') {
        return
      }

      let subscribeSelectedPlanFrequency = $('select[name=quickpaysubscription_selected_plan_frequency]')

      $.ajax({
        url: $('input[name=quickpaysubscription_ajax_url]').val(),
        cache: false,
        async: false,
        type: 'POST',
        data: {
          ajax: true,
          action: 'add',
          token: $('input[name=quickpaysubscription_token]').val(),
          id_product: resp.id_product,
          id_product_attribute: resp.id_product_attribute,
          id_customization: resp.id_customization,
          quantity: resp.quantity,
          id_plan: subscribeSelectedPlanFrequency.find(':selected').data('id_plan'),
          frequency: subscribeSelectedPlanFrequency.val(),
        },
        success: function (response) {
          if (response['status']) {
            $('#quickpaysubscription_cart_type_subscribe').click()
            subscribeSelectedPlanFrequency.find('option[value=' + response['cycle'] + '][data-id_plan=' + response['plan'] + ']')
          }
        }
      })

    }
  )

  $(document).on('click', '.quickpaysubscription_show_subscription', function (e) {
    e.preventDefault()
    e.stopPropagation()
    let _this = $(this)
    $('.quickpaysubscription_info span').html(_this.prop('title'))
    $('.quickpaysubscription_info').addClass('active')
  })

  $(document).on('click', '.quickpaysubscription_info svg', function (e) {
    e.preventDefault()
    e.stopPropagation()
    $('.quickpaysubscription_info').removeClass('active')
  });

  $(document).on('click', '.cancelSubscription', function (e) {
    e.preventDefault()
    e.stopPropagation()
    let _this = $(this);

    let cancelled = '<path fill="#ff4c4c" d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z"></path>'

    $.ajax({
      url: quickpaysubscription_ajax_url,
      cache: false,
      async: false,
      type: 'POST',
      data: {
        ajax: true,
        action: 'cancel',
        token: quickpaysubscription_token,
        id_subscription: _this.data('id'),
      },
      success: function (response) {
        response = JSON.parse(response)
        if (response['status']) {
          $('.messages').html('<div class="alert alert-success" role="alert">' + response['message'] + ' </div>').fadeIn(250)
          _this.parents('tr').find('svg').html(cancelled)
          _this.remove()
        } else {
          $('.messages').html('<div class="alert alert-error" role="alert">' + response['message'] + ' </div>').fadeIn(250)
        }
      }
    })
  });
})
