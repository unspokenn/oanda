<?php
declare(strict_types=1);

namespace Unspokenn\Oanda\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle7\{Client as GuzzleAdapter};
use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\HttpClientPool\HttpClientPoolItem;
use Http\Client\Common\HttpClientPool\RoundRobinClientPool;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\Authentication\Bearer;
use Http\Message\RequestFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Psr\Http\Message\ResponseInterface;
use Unspokenn\Oanda\HttpClientFactory;

/**
 * Abstract Class Base
 *
 * @package Unspokenn\Oanda\Http\Base
 * @method static \Http\Client\Common\Plugin\QueryDefaultsPlugin setQueryPram(array $queryParams)
 * @method static \Http\Client\Common\Plugin\ContentTypePlugin detectContentType($config = ['skip_detection' => false, 'size_limit' => 16000000])
 * @method static insert(\string[][] $array)
 */
abstract class Client
{
    private GuzzleAdapter $client;
    private array $plugins = [];
    protected string $baseUrl;
//    private array $map = [
//        'setQueryPram' => \Http\Client\Common\Plugin\QueryDefaultsPlugin::class,
//        'detectContentType' => \Http\Client\Common\Plugin\ContentTypePlugin::class,
//
//    ];
    /**
     * @var \Http\Message\RequestFactory
     */
    private RequestFactory $requestFactory;

    public function __construct(
        private StreamFactory $streamFactory,
        private UriFactory    $uriFactory
    )
    {
        $this->requestFactory = new RequestFactory();
    }

    public function withBearerAuth(string $api_key):  {
        $this->plugins[] = new AuthenticationPlugin(new Bearer($api_key));
    }

//    public function __construct($api_key)
//    {
////        $queryDefaultsPlugin = new QueryDefaultsPlugin([
////            'locale' => 'en'
////        ]);
//
//        $pluginClient = new PluginClient(
//            HttpClientDiscovery::find(),
//            [] + $this->plugin
//        );
//
//
////        $contentTypePlugin = new ContentTypePlugin();
//
//        $authentication = ;
//        $authenticationPlugin = ;
//        $decoderPlugin = new DecoderPlugin();
//
//        $pluginClient = new PluginClient(
//            HttpClientDiscovery::find(),
//            [
//                new RetryPlugin(['retries' => 3]),
//                new ErrorPlugin(),
//                new AuthenticationPlugin(new Bearer($api_key)),
//                $this->setHeader(),
//            ]
//        );
//
//        $this->client = new GuzzleAdapter(new GuzzleClient(['timeout' => 3]));
//
//        $this->client->sendRequest()
//    }

    public function newPoolClient()
    {
        $messageFactory = Psr17FactoryDiscovery::findRequestFactory();

        $httpClient = HttpClientDiscovery::find();
        $httpAsyncClient = HttpAsyncClientDiscovery::find();

        $httpClientPool = new LeastUsedClientPool();
        $httpClientPool->addHttpClient($httpClient);
        $httpClientPool->addHttpClient($httpAsyncClient);

        $httpClientPool->sendRequest($messageFactory->createRequest('GET', 'http://example.com/update'));
    }

    public function setPool()
    {
        $httpClientPool = new RoundRobinClientPool();
        $httpClientPool->addHttpClient(new HttpClientPoolItem($this->client));
    }

    /**
     * Prepare an HTTP request using a Guzzle client
     *
     * @param string $endpoint API endpoint
     * @param string $method Optional HTTP method
     * @param mixed|null $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return Request
     */
    protected function prepareRequest(string $endpoint, string $method = 'GET', mixed $data = null, array $headers = []): Request
    {
        $headers += [
            'Authorization' => $this->bearerToken(),
            'Content-Type' => 'application/json'
        ];

        // Handle data
        if ($method == 'GET') {
            $endpoint = $this->absoluteEndpoint($endpoint, $data);
            $body = null;
        } else {
            $endpoint = $this->absoluteEndpoint($endpoint);
            $body = ($data !== null) ? $this->jsonEncode($data) : null;
        }

        return new Request($method, $endpoint, $headers, $body);
    }

    /**
     * Send an HTTP request
     *
     * @param \GuzzleHttp\Psr7\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function sendRequest(Request $request): ResponseInterface
    {
        $client = HttpClientFactory::create($this-)new GuzzleAdapter(new GuzzleClient(['timeout' => 3]));
        $response = null;
        try {
            $response = $client->sendRequest($request);
        } catch (ClientErrorException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                // set token 2
                $response = $this->sendRequest($request);
            }
        }
        return $response;
    }

    /**
     * Helper method to automatically send a GET request and return the decoded response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function makeGetRequest(string $endpoint, array $data = [], array $headers = []): mixed
    {
        $request = $this->prepareRequest($endpoint, 'GET', $data, $headers);
        $response = $this->sendRequest($request);

        return $this->jsonDecode((string)$response->getBody());
    }

    /**
     * Helper method to automatically send a POST request and return the HTTP response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function makePostRequest(string $endpoint, array $data = [], array $headers = []): ResponseInterface
    {
        $request = $this->prepareRequest($endpoint, 'POST', $data, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Helper method to automatically send a PATCH request and return the HTTP response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function makePatchRequest(string $endpoint, array $data = [], array $headers = []): ResponseInterface
    {
        $request = $this->prepareRequest($endpoint, 'PATCH', $data, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Parse a complete API url given an endpoint
     *
     * @param string $endpoint
     * @param array $data Optional query string parameters
     * @return string
     */
    protected function absoluteEndpoint(string $endpoint, array $data = []): string
    {
        $url = parse_url($endpoint);

        if (isset($url['query'])) {
            parse_str($url['query'], $data);
        }

        return $this->baseUri()
            . '/'
            . trim($url['path'], '/')
            . (!empty($data) ? '?' . http_build_query($data) : '');
    }

    /**
     * Encode data as JSON
     *
     * @param mixed $data
     * @return string
     */
    protected function jsonEncode(mixed $data): string
    {
        return json_encode($data);
    }

    /**
     * Decode JSON using arrays (not objects)
     *
     * @param string $data
     * @return mixed
     */
    protected function jsonDecode(string $data): mixed
    {
        return json_decode($data, true);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (array_key_exists($method, $this->plugin)) {
            call_user_func([$this->plugin[$method], 'method'], ...$parameters);

        }

        return $this->$method(...$parameters);
    }
}


