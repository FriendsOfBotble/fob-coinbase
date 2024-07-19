<?php

namespace FriendsOfBotble\Coinbase\Http\Controllers;

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
        $secret = setting('payment_coinbase_webhook_key');
        $signature = $request->header('X-Cc-Webhook-Signature');
        $payload = $request->getContent();

        logger()->info('[COINBASE] Webhook Received (signature: ' . $signature . '): ' . $payload);

        try {
            $coinbasePaymentService->setClient();

            $event = Webhook::buildEvent($payload, $signature, $secret);
            $data = json_decode($payload, true);
            $chargeId = $data['event']['data']['id'] ?? null;

            abort_unless($chargeId, SymfonyResponse::HTTP_NOT_FOUND);

            $payment = Payment::query()
                ->where('charge_id', $chargeId)
                ->first();

            if (! $payment) {
                $coinbasePaymentService->afterMakePayment($chargeId);
            }

            switch ($event->getAttribute('type')) {
                case 'charge:confirmed':
                    $payment->status = PaymentStatusEnum::COMPLETED;
                    $payment->save();

                    break;
                case 'charge:failed':
                    $payment->status = PaymentStatusEnum::FAILED;
                    $payment->save();

                    break;
            }

            return response('Ok.');
        } catch (ModelNotFoundException) {
            return response('Not found.');
        } catch (Exception $exception) {
            report($exception);

            return response($exception->getMessage());
        }
    }
}
