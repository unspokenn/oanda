<?php

namespace Unspokenn\Oanda;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\HeaderSetPlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Message\Authentication\Bearer;
use Illuminate\Support\Facades\Log;

class HttpClientFactory
{

    public function __construct(string $api_key, array $plugins = [])
    {

    }

    /**
     * Build the HTTP client to talk with the API.
     *
     * @param string $api_key
     * @param \Http\Client\Common\Plugin[] $plugins
     * @return HttpClient
     */
    public static function create(): PluginClient|HttpClient
    {
        $client = new GuzzleAdapter(new GuzzleClient(['timeout' => 3]));
        $plugins[] = new ErrorPlugin();
        $plugins[] = new RetryPlugin();
        $plugins[] = new AuthenticationPlugin(new Bearer($api_key));
        $plugins[] = new DecoderPlugin(['use_content_encoding' => false]);
        $plugins[] = new RedirectPlugin();
        $plugins[] = new LoggerPlugin(Log::channel('oanda'));
        $plugins[] = new HeaderSetPlugin([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
        return new PluginClient($client, $plugins);
    }
}
