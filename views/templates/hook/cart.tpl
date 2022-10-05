<div class="quickpay-subscription-cart-status" style="display: none">
  <div class="col-md-12 alert-danger text-black py-1 my-1">
      {l s='The cart contains both subsciption and not subscription products or the subscription product\'s frequency are difference. Please remove one of type to continue' mod='quickpaysubscription'}
  </div>
</div>

<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function () {
    window.setTimeout(function () {
      $.ajax({
        url: quickpaysubscription_ajax_url,
        cache: false,
        async: false,
        type: 'POST',
        data: {
          ajax: true,
          action: 'check',
          token: quickpaysubscription_token
        },
        success: function (response) {
          response = JSON.parse(response)
          if (response['status'] === true) {
            document.querySelector('.checkout.cart-detailed-actions a').classList.add('disabled')
            document.querySelector('.quickpay-subscription-cart-status').style.display = 'block'
          } else {
            document.querySelector('.checkout.cart-detailed-actions a').classList.remove('disabled')
            document.querySelector('.quickpay-subscription-cart-status').style.display = 'none'
          }
        }
      })
    }, 250);

    prestashop.on('updateCart',
      function (e) {
        window.setTimeout(function () {
          $.ajax({
            url: quickpaysubscription_ajax_url,
            cache: false,
            async: false,
            type: 'POST',
            data: {
              ajax: true,
              action: 'check',
              token: quickpaysubscription_token
            },
            success: function (response) {
              response = JSON.parse(response)
              if (response['status'] === true) {
                document.querySelector('.checkout.cart-detailed-actions a').classList.add('disabled')
                document.querySelector('.quickpay-subscription-cart-status').style.display = 'block'
              } else {
                document.querySelector('.checkout.cart-detailed-actions a').classList.remove('disabled')
                document.querySelector('.quickpay-subscription-cart-status').style.display = 'none'
              }
            }
          })
        }, 250);

      });
  })
</script>


<div class="quickpaysubscription_info">
  <svg viewBox="0 0 24 24">
    <path fill="currentColor" d="M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2C6.47,2 2,6.47 2,12C2,17.53 6.47,22 12,22C17.53,22 22,17.53 22,12C22,6.47 17.53,2 12,2M14.59,8L12,10.59L9.41,8L8,9.41L10.59,12L8,14.59L9.41,16L12,13.41L14.59,16L16,14.59L13.41,12L16,9.41L14.59,8Z" />
  </svg>
  <p>{l s='Subscription information' mod='quickpaysubscription'}</p>
  <span></span>
</div>
