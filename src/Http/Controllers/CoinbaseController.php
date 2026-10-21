<?php

namespace FriendsOfBotble\Coinbase\Http\Controllers;

use FriendsOfBotble\Coinbase\Library\Exceptions\SignatureVerificationException;
use FriendsOfBotble\Coinbase\Library\Webhook;
use FriendsOfBotble\Coinbase\Services\Gateways\CoinbasePaymentService;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CoinbaseController extends Controller
{
    public function success(
        BaseHttpResponse $response,
        CoinbasePaymentService $coinbasePaymentService
    ): BaseHttpResponse {
        $chargeId = session('coinbase_charge_id');

        if (! $chargeId) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage(__('Payment failed!'));
        }

        try {
            if ($coinbasePaymentService->afterMakePayment($chargeId) && ! $coinbasePaymentService->getErrorMessage()) {
                session()->forget('coinbase_charge_id');

                return $response
                    ->setNextUrl(PaymentHelper::getRedirectURL() . '?charge_id=' . $chargeId)
                    ->setMessage(__('Checkout successfully!'));
            }

            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage($coinbasePaymentService->getErrorMessage() ?: __('Payment failed!'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->withInput()
                ->setMessage($exception->getMessage() ?: __('Payment failed!'));
        }
    }

    public function error(BaseHttpResponse $response): BaseHttpResponse
    {
        return $response
            ->setError()
            ->setNextUrl(PaymentHelper::getCancelURL())
            ->withInput()
            ->setMessage(__('Payment failed!'));
    }

    public function webhook(Request $request, CoinbasePaymentService $coinbasePaymentService): Response
    {
        $secret = (string) setting('payment_coinbase_webhook_key');
        $signature = (string) $request->header('X-Cc-Webhook-Signature');
        $payload = $request->getContent();

        logger()->info('[COINBASE] Webhook Received: ' . $payload);

        // Without a shared secret the HMAC can be computed by anyone, so refuse instead of accepting.
        if ($secret === '' || $signature === '') {
            return response('Invalid signature.', SymfonyResponse::HTTP_BAD_REQUEST);
        }

        try {
            $coinbasePaymentService->setClient();

            $event = Webhook::buildEvent($payload, $signature, $secret);
            $data = json_decode($payload, true);
            $chargeId = $data['event']['data']['id'] ?? null;

            // Coinbase retries any non-2xx response for days, so acknowledge what can never succeed.
            if (! $chargeId) {
                return response('Not found.');
            }

            $payment = Payment::query()
                ->where('charge_id', $chargeId)
                ->first();

            if (! $payment) {
                $coinbasePaymentService->afterMakePayment($chargeId);

                $payment = Payment::query()
                    ->where('charge_id', $chargeId)
                    ->first();
            }

            if (! $payment) {
                return response('Not found.');
            }

            if ($payment->status == PaymentStatusEnum::COMPLETED) {
                return response('Ok.');
            }

            switch ($event->getAttribute('type')) {
                case 'charge:confirmed':
                    if (! $this->isFullyPaid($payment, $data['event']['data'] ?? [])) {
                        logger()->warning('[COINBASE] Confirmed charge does not cover payment ' . $payment->getKey());

                        return response('Amount mismatch.');
                    }

                    $payment->status = PaymentStatusEnum::COMPLETED;
                    $payment->save();

                    break;
                case 'charge:failed':
                    $payment->status = PaymentStatusEnum::FAILED;
                    $payment->save();

                    break;
            }

            return response('Ok.');
        } catch (SignatureVerificationException $exception) {
            report($exception);

            return response('Invalid signature.', SymfonyResponse::HTTP_BAD_REQUEST);
        } catch (ModelNotFoundException) {
            return response('Not found.');
        } catch (Exception $exception) {
            report($exception);

            return response('Error.', SymfonyResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Coinbase only sends charge:confirmed for a fully paid charge, so this is a second check: pricing.local is the
     * price that was asked for, the amounts actually received are in payments[].value.local. When the event carries
     * no usable payment data, trust the confirmation as before.
     */
    protected function isFullyPaid(Payment $payment, array $charge): bool
    {
        $currency = strtoupper((string) $payment->currency);
        $payments = collect($charge['payments'] ?? [])
            ->filter(fn ($item) => is_array($item) && is_numeric(data_get($item, 'value.local.amount')))
            ->reject(fn ($item) => isset($item['status']) && strtoupper((string) $item['status']) !== 'CONFIRMED');

        if ($payments->isEmpty()) {
            logger()->info('[COINBASE] Confirmed charge without payment details for payment ' . $payment->getKey());

            return true;
        }

        if ($payments->contains(fn ($item) => strtoupper((string) data_get($item, 'value.local.currency')) !== $currency)) {
            return false;
        }

        $paid = $payments->sum(fn ($item) => (float) data_get($item, 'value.local.amount'));

        return round($paid, 2) >= round((float) $payment->amount, 2);
    }
}
