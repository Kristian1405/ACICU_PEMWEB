{{--
  partials/acicu-config.blade.php
  ------------------------------------------------------------------
  Menyuntikkan konfigurasi runtime (Firebase web config + Midtrans
  public client key + endpoint) ke JavaScript. Nilai sensitif (Server
  Key) TIDAK pernah dikirim ke browser.
--}}
<script>
  window.ACICU_CONFIG = {
    firebase: @json(config('firebase.web')),
    midtrans: {
      clientKey: @json(config('midtrans.client_key')),
      scriptUrl: @json(config('midtrans.snap_script_url')),
      isProduction: @json((bool) config('midtrans.is_production')),
    },
    endpoints: {
      snapToken: @json(route('acicu.payment.snap')),
      verify: @json(route('acicu.payment.verify')),
      status: @json(route('acicu.status.check', ['order' => '__ORDER_ID__'])),
    },
  };
</script>
