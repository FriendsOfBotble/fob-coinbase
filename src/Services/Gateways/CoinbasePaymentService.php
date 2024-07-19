<?php

namespace FriendsOfBotble\Coinbase\Services\Gateways;

use FriendsOfBotble\Coinbase\Library\Resources\Charge;
use FriendsOfBotble\Coinbase\Services\Abstracts\CoinbasePaymentAbstract;
use Botble\Payment\Enums\PaymentStatusEnum;
use Exception;
use Illuminate\Support\Arr;

class CoinbasePaymentService extends CoinbasePaymentAbstract
{
    public function makePayment(array $data): string|null
    {
        try {
            $this->setClient();

            $result = Charge::create([
                'name' => $data['description'],
                'description' => $data['description'],
                'local_price' => [
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                ],
                'metadata' => [
                    'customer_id' => $data['customer_id'],
                    'customer_type' => $data['customer_type'],
                    'order_id' => $data['order_id'],
                ],
                'pricing_type' => 'fixed_price',
                'redirect_url' => route('payments.coinbase.success'),
                'cancel_url' => route('payments.coinbase.error'),
            ]);

            if ($result) {
                if (is_array($result)) {
                    $checkoutUrl = Arr::get($result, 'hosted_url');
                    $chargeId = Arr::get($result, 'id');
                } else {
                    $checkoutUrl = $result->getAttribute('hosted_url');
                    $chargeId = $result->getAttribute('id');
                }

                session()->put('coinbase_charge_id', $chargeId);

                return $checkoutUrl;
            }
        } catch (Exception $exception) {
            $this->setErrorMessage($exception->getMessage());
        }

        return null;
    }

    public function afterMakePayment(string $chargeId): string
    {
        try {
            $this->setClient();

            $charge = Charge::retrieve($chargeId);

            if ($charge && $pricing = $charge->getAttribute('pricing')['local']) {
                $metadata = $charge->getAttribute('metadata');

                do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                    'amount' => $pricing['amount'],
                    'currency' => $pricing['currency'],
                    'charge_id' => $charge->getAttribute('id'),
                    'order_id' => $metadata['order_id'],
                    'customer_id' => $metadata['customer_id'],
                    'customer_type' => $metadata['customer_type'],
                    'payment_channel' => COINBASE_PAYMENT_METHOD_NAME,
                    'status' => PaymentStatusEnum::PENDING,
                ]);

                return $chargeId;
            }
        } catch (Exception $exception) {
            logger()->error($exception->getMessage());

            $this->setErrorMessage($exception->getMessage());

            return $chargeId;
        }

        return $chargeId;
    }
}
