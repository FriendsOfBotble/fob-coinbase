@if (setting('payment_coinbase_status') == 1)
    <li class="list-group-item">
        <input class="magic-radio js_payment_method" type="radio" name="payment_method" id="payment_coinbase" value="coinbase" @if ((session('selected_payment_method') ?: setting('default_payment_method')) == COINBASE_PAYMENT_METHOD_NAME) checked @endif data-bs-toggle="collapse" data-bs-target=".payment_coinbase_wrap" data-toggle="collapse" data-target=".payment_coinbase_wrap" data-parent=".list_payment_method">
        <label for="payment_coinbase" class="text-start">
            {{ setting('payment_coinbase_name', trans('plugins/payment::payment.payment_via_card')) }}
        </label>
        <div class="payment_coinbase_wrap payment_collapse_wrap collapse @if ((session('selected_payment_method') ?: setting('default_payment_method')) == COINBASE_PAYMENT_METHOD_NAME) show @endif" style="padding: 15px 0;">
            <p>{!! BaseHelper::clean(get_payment_setting('description', COINBASE_PAYMENT_METHOD_NAME)) !!}</p>
        </div>
    </li>
@endif
