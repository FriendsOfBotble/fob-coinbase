<?php

namespace FriendsOfBotble\Coinbase\Library\Resources\Operations;

use Exception;

use function is_scalar;

trait SaveMethodTrait
{
    public function save($headers = [])
    {
        $id = $this->getPrimaryKeyValue();

        if (is_scalar($id) && ! method_exists($this, 'update')) {
            throw new Exception('Update is not allowed');
        }

        return $id ? $this->update() : $this->insert();
    }
}
