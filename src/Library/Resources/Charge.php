<?php

namespace FriendsOfBotble\Coinbase\Library\Resources;

use FriendsOfBotble\Coinbase\Library\Resources\Operations\CreateMethodTrait;
use FriendsOfBotble\Coinbase\Library\Resources\Operations\ReadMethodTrait;
use FriendsOfBotble\Coinbase\Library\Resources\Operations\SaveMethodTrait;
use FriendsOfBotble\Coinbase\Library\Util;

class Charge extends ApiResource implements ResourcePathInterface
{
    use CreateMethodTrait;
    use ReadMethodTrait;
    use SaveMethodTrait;

    /**
     * @return string
     */
    public static function getResourcePath()
    {
        return 'charges';
    }

    public function resolve($headers = [])
    {
        $id = $this->id;
        $path = Util::joinPath(static::getResourcePath(), $id, 'resolve');
        $client = static::getClient();
        $response = $client->post($path, [], $headers);
        $this->refreshFrom($response);
    }

    public function cancel($headers = [])
    {
        $id = $this->id;
        $path = Util::joinPath(static::getResourcePath(), $id, 'cancel');
        $client = static::getClient();
        $response = $client->post($path, [], $headers);
        $this->refreshFrom($response);
    }
}
