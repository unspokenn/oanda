<?php

namespace Unspokenn\Oanda\Http;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\{Client as GuzzleAdapter};
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Message\Authentication\Bearer;

class Client
{
    public function __construct($api_key)
    {
        $authentication = new Bearer($api_key);
        $authenticationPlugin = new AuthenticationPlugin($authentication);

        $pluginClient = new PluginClient(
            HttpClientDiscovery::find(),
            [$authenticationPlugin]
        );
        $config = ['timeout' => 5];
// ...
        $guzzle = new GuzzleClient($config);
// ...
        $adapter = new GuzzleAdapter($guzzle);
    }
}


