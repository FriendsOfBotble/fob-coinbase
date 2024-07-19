@php $coinbaseStatus = setting('payment_coinbase_status'); @endphp
<table class="table payment-method-item">
    <tbody><tr class="border-pay-row">
        <td class="border-pay-col"><i class="fa fa-theme-payments"></i></td>
        <td style="width: 20%;">
            <img class="filter-black" src="{{ url('vendor/core/plugins/coinbase/images/coinbase.svg') }}" alt="coinbase">
        </td>
        <td class="border-right">
            <ul>
                <li>
                    <a href="https://coinbase.com" target="_blank">Coinbase</a>
                    <p>{{ trans('plugins/coinbase::coinbase.description') }}</p>
                </li>
            </ul>
        </td>
    </tr>
    <tr class="bg-white">
        <td colspan="3">
            <div class="float-start" style="margin-top: 5px;">
                <div class="payment-name-label-group @if ($coinbaseStatus == 0) hidden @endif">
                    <span class="payment-note v-a-t">{{ trans('plugins/payment::payment.use') }}:</span> <label class="ws-nm inline-display method-name-label">{{ setting('payment_coinbase_name') }}</label>
                </div>
            </div>
            <div class="float-end">
                <a class="btn btn-secondary toggle-payment-item edit-payment-item-btn-trigger @if ($coinbaseStatus == 0) hidden @endif">{{ trans('plugins/payment::payment.edit') }}</a>
                <a class="btn btn-secondary toggle-payment-item save-payment-item-btn-trigger @if ($coinbaseStatus == 1) hidden @endif">{{ trans('plugins/payment::payment.settings') }}</a>
            </div>
        </td>
    </tr>
    <tr class="paypal-online-payment payment-content-item hidden">
        <td class="border-left" colspan="3">
            {!! Form::open() !!}
            {!! Form::hidden('type', COINBASE_PAYMENT_METHOD_NAME, ['class' => 'payment_type']) !!}
            <div class="row">
                <div class="col-sm-6">
                    <ul>
                        <li>
                            <label>{{ trans('plugins/payment::payment.configuration_instruction', ['name' => 'Coinbase']) }}</label>
                        </li>
                        <li class="payment-note">
                            <p>{{ trans('plugins/payment::payment.configuration_requirement', ['name' => 'Coinbase']) }}:</p>
                            <ul class="m-md-l" style="list-style-type:decimal">
                                <li style="list-style-type:decimal">
                                    <p>
                                        <a href="https://beta.commerce.coinbase.com/" target="_blank">
                                            {{ trans('plugins/payment::payment.service_registration', ['name' => 'Coinbase']) }}
                                        </a>
                                    </p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{!! BaseHelper::clean(trans('plugins/coinbase::coinbase.after_service_registration_msg')) !!}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ trans('plugins/coinbase::coinbase.enter_api_key') }}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{!! BaseHelper::clean(trans('plugins/coinbase::coinbase.get_webhook_secret_key_msg')) !!}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ trans('plugins/coinbase::coinbase.enter_webhook_secret_key_msg') }}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{!! BaseHelper::clean(trans('plugins/coinbase::coinbase.enter_webhook_url_msg')) !!}</p>
                                    <p>
                                        <code>{{ route('payments.coinbase.webhook') }}</code>
                                    </p>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="col-sm-6">
                    <div class="well bg-white">
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="coinbase_name">{{ trans('plugins/payment::payment.method_name') }}</label>
                            <input type="text" class="next-input input-name" name="payment_coinbase_name" id="coinbase_name" data-counter="400" value="{{ setting('payment_coinbase_name', trans('plugins/payment::payment.pay_online_via', ['name' => 'Coinbase'])) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_coinbase_description">{{ trans('core/base::forms.description') }}</label>
                            <textarea class="next-input" name="payment_coinbase_description" id="payment_coinbase_description">{{ get_payment_setting('description', 'coinbase', __('Payment with Coinbase')) }}</textarea>
                        </div>
                        <p class="payment-note">
                            {{ trans('plugins/payment::payment.please_provide_information') }} <a target="_blank" href="//www.coinbase.com">Coinbase</a>:
                        </p>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_coinbase_api_key">{{ trans('plugins/coinbase::coinbase.api_key') }}</label>
                            <input type="text" class="next-input" name="payment_coinbase_api_key" id="payment_coinbase_api_key" placeholder="**************************" value="{{ app()->environment('demo') ? '*******************************' : setting('payment_coinbase_api_key') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_coinbase_webhook_key">{{ trans('plugins/coinbase::coinbase.webhook_secret_key') }}</label>
                            <input type="text" class="next-input" name="payment_coinbase_webhook_key" id="payment_coinbase_webhook_key" placeholder="**************************" value="{{ app()->environment('demo') ? '*******************************' : setting('payment_coinbase_webhook_key') }}">
                        </div>

                        {!! apply_filters(PAYMENT_METHOD_SETTINGS_CONTENT, null, 'coinbase') !!}
                    </div>
                </div>
            </div>
            <div class="col-12 bg-white text-end">
                <button class="btn btn-warning disable-payment-item @if ($coinbaseStatus == 0) hidden @endif" type="button">{{ trans('plugins/payment::payment.deactivate') }}</button>
                <button class="btn btn-info save-payment-item btn-text-trigger-save @if ($coinbaseStatus == 1) hidden @endif" type="button">{{ trans('plugins/payment::payment.activate') }}</button>
                <button class="btn btn-info save-payment-item btn-text-trigger-update @if ($coinbaseStatus == 0) hidden @endif" type="button">{{ trans('plugins/payment::payment.update') }}</button>
            </div>
            {!! Form::close() !!}
        </td>
    </tr>
    </tbody>
</table>
