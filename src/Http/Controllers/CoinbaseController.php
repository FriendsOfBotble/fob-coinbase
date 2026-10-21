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

            if (! $chargeId) {
                return response('Not found.', SymfonyResponse::HTTP_NOT_FOUND);
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
                return response('Not found.', SymfonyResponse::HTTP_NOT_FOUND);
            }

            if ($payment->status == PaymentStatusEnum::COMPLETED) {
                return response('Ok.');
            }

            switch ($event->getAttribute('type')) {
                case 'charge:confirmed':
                    if (! $this->matchesPaymentAmount($payment, $data['event']['data']['pricing']['local'] ?? null)) {
                        logger()->warning('[COINBASE] Charge amount does not match payment ' . $payment->getKey());

                        return response('Amount mismatch.', SymfonyResponse::HTTP_BAD_REQUEST);
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

    protected function matchesPaymentAmount(Payment $payment, ?array $localPricing): bool
    {
        if (! $localPricing || ! isset($localPricing['amount'], $localPricing['currency'])) {
            return false;
        }

        return strtoupper((string) $localPricing['currency']) === strtoupper((string) $payment->currency)
            && round((float) $localPricing['amount'], 2) >= round((float) $payment->amount, 2);
    }
}
