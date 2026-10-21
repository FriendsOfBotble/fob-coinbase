<?php

namespace FriendsOfBotble\Coinbase\Library\Exceptions;

class SignatureVerificationException extends CoinbaseException
{
    public function __construct($signature, $payload)
    {
        // Never include the computed signature: it would let anyone forge webhooks.
        parent::__construct('No signatures found matching the expected signature for the webhook payload.');
    }
}
