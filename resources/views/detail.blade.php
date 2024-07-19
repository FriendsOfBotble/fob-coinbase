@if ($payment)
    <div class="alert alert-success" role="alert">
        <p class="mb-2">{{ trans('plugins/payment::payment.payment_id') }}: <strong>{{ data_get($payment, 'id') }}</strong></p>
        <p class="mb-2">{{ trans('plugins/coinbase::coinbase.charge_code') }}: <strong>{{ data_get($payment, 'code') }}</strong></p>
    </div>

    @include('plugins/payment::partials.view-payment-source')
@endif
