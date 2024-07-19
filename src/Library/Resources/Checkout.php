<?php

namespace FriendsOfBotble\Coinbase\Library\Resources;

use FriendsOfBotble\Coinbase\Library\Resources\Operations\CreateMethodTrait;
use FriendsOfBotble\Coinbase\Library\Resources\Operations\DeleteMethodTrait;
use FriendsOfBotble\Coinbase\Library\Resources\Operations\ReadMethodTrait;
use FriendsOfBotble\Coinbase\Library\Resources\Operations\SaveMethodTrait;
use FriendsOfBotble\Coinbase\Library\Resources\Operations\UpdateMethodTrait;

class Checkout extends ApiResource implements ResourcePathInterface
{
    use ReadMethodTrait;
    use CreateMethodTrait;
    use UpdateMethodTrait;
    use DeleteMethodTrait;
    use SaveMethodTrait;

    /**
     * @return string
     */
    public static function getResourcePath()
    {
        return 'checkouts';
    }
}
