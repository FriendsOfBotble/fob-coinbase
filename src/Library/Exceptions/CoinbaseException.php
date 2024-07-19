<?php

namespace FriendsOfBotble\Coinbase\Library\Exceptions;

use Exception;

class CoinbaseException extends Exception
{
    public static function getClassName()
    {
        return get_called_class();
    }
}
