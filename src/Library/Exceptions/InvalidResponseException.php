<?php

namespace FriendsOfBotble\Coinbase\Library\Exceptions;

class InvalidResponseException extends CoinbaseException
{
    public function __construct($message = '', $body = '')
    {
        parent::__construct($message);
        $this->body = $body;
    }
}
