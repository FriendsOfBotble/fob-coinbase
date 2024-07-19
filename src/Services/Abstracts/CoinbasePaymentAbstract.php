<?php

namespace FriendsOfBotble\Coinbase\Services\Abstracts;

use FriendsOfBotble\Coinbase\Library\ApiClient;
use FriendsOfBotble\Coinbase\Library\Resources\Charge;
use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Exception;

abstract class CoinbasePaymentAbstract
{
    use PaymentErrorTrait;

    protected bool $supportRefundOnline = false;

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    abstract public function makePayment(array $data);

    abstract public function afterMakePayment(string $chargeId);

    public function setClient(): self
    {
        ApiClient::init(setting('payment_coinbase_api_key'));

        return $this;
    }

    public function getPaymentDetails(string $chargeId): ?Charge
    {
        $this->setClient();

        try {
            return Charge::retrieve($chargeId);
        } catch (Exception) {
            return null;
        }
    }
}
