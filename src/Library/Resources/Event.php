<?php

namespace FriendsOfBotble\Coinbase\Library\Resources;

use FriendsOfBotble\Coinbase\Library\Resources\Operations\ReadMethodTrait;

class Event extends ApiResource implements ResourcePathInterface
{
    use ReadMethodTrait;

    /**
     * @return string
     */
    public static function getResourcePath()
    {
        return 'events';
    }
}
