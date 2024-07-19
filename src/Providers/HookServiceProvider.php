<?php

namespace FriendsOfBotble\Coinbase\Providers;

use FriendsOfBotble\Coinbase\Library\ApiClient;
use FriendsOfBotble\Coinbase\Library\Resources\Charge;
use FriendsOfBotble\Coinbase\Services\Gateways\CoinbasePaymentService;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Models\Payment;
use Html;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data) {
            return $html . view('plugins/coinbase::methods', $data)->render();
        }, 1, 2);

        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request) {
            if ($data['type'] !== COINBASE_PAYMENT_METHOD_NAME) {
                return $data;
            }

            $paymentService = app(CoinbasePaymentService::class)->setClient();
            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            $result = $paymentService->makePayment($paymentData);

            if ($result) {
                $data['checkoutUrl'] = $result;
            } else {
                $data['error'] = true;
                $data['message'] = $paymentService->getErrorMessage();
            }

            return $data;
        }, 1, 2);

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings) {
            return $settings . view('plugins/coinbase::settings')->render();
        }, 1);

        add_filter(BASE_FILTER_ENUM_ARRAY, function (array $values, string $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['COINBASE'] = COINBASE_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 1, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function (string $value, string $class) {
            if ($class == PaymentMethodEnum::class && $value == COINBASE_PAYMENT_METHOD_NAME) {
                $value = 'Coinbase';
            }

            return $value;
        }, 1, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function (string $value, string $class) {
            if ($class == PaymentMethodEnum::class && $value == COINBASE_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 1, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == COINBASE_PAYMENT_METHOD_NAME) {
                $data = CoinbasePaymentService::class;
            }

            return $data;
        }, 1, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function (string $data, Payment $payment) {
            if ($payment->payment_channel === COINBASE_PAYMENT_METHOD_NAME) {
                ApiClient::init(setting('payment_coinbase_api_key'));

                try {
                    $paymentDetail = Charge::retrieve($payment->charge_id);
                } catch (Throwable) {
                    return $data;
                }

                $data .= view('plugins/coinbase::detail', ['payment' => $paymentDetail->getAttributes()])->render();
            }

            return $data;
        }, 1, 2);
    }
}
