<?php

namespace FriendsOfBotble\Coinbase;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Models\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::query()
            ->whereIn('key', [
                'payment_coinbase_name',
                'payment_coinbase_description',
                'payment_coinbase_status',
                'payment_coinbase_api_key',
                'payment_coinbase_webhook_key',
            ])->delete();
    }
}
