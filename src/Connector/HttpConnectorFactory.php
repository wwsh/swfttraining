<?php

namespace App\Connector;

use GuzzleHttp\Client;

/**
 * @package App\Connector
 */
class HttpConnectorFactory
{
    /**
     * @param array $params
     * @return Client
     */
    public static function create(array $params)
    {
        $client = new Client($params);

        return $client;
    }
}
