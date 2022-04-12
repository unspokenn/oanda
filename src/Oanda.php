<?php
declare(strict_types=1);

namespace Unspokenn\Oanda;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Http\Client\Common\Plugin\{DecoderPlugin,
    ErrorPlugin,
    HeaderSetPlugin,
    LoggerPlugin,
    RedirectPlugin,
    ResponseSeekableBodyPlugin,
    RetryPlugin};
use Http\Client\Common\PluginClient;
use Illuminate\Support\Facades\{Config, Log};

class Oanda
{
    /**
     * Defines the LIVE API url
     *
     * @const URL_LIVE
     */
    const URL_LIVE = 'https://api-fxtrade.oanda.com';
    /**
     * Defines the LIVE STREAM API url
     *
     * @const URL_PRACTICE
     */
    const URL_STREAM_LIVE = 'https://stream-fxtrade.oanda.com';
    /**
     * Defines the PRACTICE API url
     *
     * @const URL_PRACTICE
     */
    const URL_PRACTICE = 'https://api-fxpractice.oanda.com';
    /**
     * Defines the PRACTICE STREAM API url
     *
     * @const URL_PRACTICE
     */
    const URL_STREAM_PRACTICE = 'https://stream-fxpractice.oanda.com';
    /**
     * Defines the LIVE API environment
     *
     * @const ENV_LIVE
     */
    const ENV_LIVE = 1;

    /**
     * Defines the PRACTICE API environment
     *
     * @const ENV_PRACTICE
     */
    const ENV_PRACTICE = 2;

    /**
     * API environment for current connection
     *
     * @var integer
     */
    protected int $apiEnvironment;

    /**
     * API key for current connection
     *
     * @var string
     */
    protected string $apiKey;
    /**
     * API account for current connection
     *
     * @var string
     */
    protected string $accountId;

    /**
     * @var bool
     */
    private bool $stream = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setApiEnvironment(Config::get('oanda.environment'));
        $this->setApiKey(Config::get('oanda.api_key.key'));
        $this->setAccountId(Config::get('oanda.api_key.account'));
    }

    /**
     * Return the current API environment
     *
     * @return integer
     */
    public function getApiEnvironment(): int
    {
        return $this->apiEnvironment;
    }

    /**
     * Set the API environment
     *
     * @param integer $apiEnvironment
     * @return \Unspokenn\Oanda\Oanda $this
     */
    public function setApiEnvironment(int $apiEnvironment): static
    {
        if ($apiEnvironment == static::ENV_LIVE || $apiEnvironment == static::ENV_PRACTICE) {
            $this->apiEnvironment = $apiEnvironment;
        } else {
            throw new \InvalidArgumentException(sprintf(
                '%s is invalid environment..select one of them: ENV_LIVE = %s, ENV_PRACTICE = %s', $apiEnvironment, static::ENV_LIVE, static::ENV_PRACTICE
            ));
        }

        return $this;
    }

    /**
     * Return the current API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Set the API key
     *
     * @param string $apiKey
     * @return \Unspokenn\Oanda\Oanda $this
     */
    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Return the current API key
     *
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Set the API key
     *
     * @param string $accountId
     * @return \Unspokenn\Oanda\Oanda $this
     */
    public function setAccountId(string $accountId): static
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * @param array $plugins
     * @return array
     */
    public function preparePlugins(array $plugins = []): array
    {
        $plugins[] = new ErrorPlugin();
        $plugins[] = new RetryPlugin();
        $plugins[] = new DecoderPlugin(['use_content_encoding' => false]);
        $plugins[] = new RedirectPlugin();
        $plugins[] = new LoggerPlugin(Log::channel('oanda'));
        return $plugins;
    }

    /**
     * @param array $plugins
     * @param array $options
     * @return \Psr\Http\Client\ClientInterface|\Http\Client\HttpAsyncClient
     */
    public function client(array $plugins = [], array $options = ['timeout' => 3]): \Psr\Http\Client\ClientInterface|\Http\Client\HttpAsyncClient
    {
        return new PluginClient(new \Http\Adapter\Guzzle7\Client(new GuzzleClient($options)), $plugins, ['max_restarts' => 2]);
    }

    /**
     * Prepare an Stream HTTP request using a Guzzle client
     *
     * @param string $endpoint API endpoint
     * @param mixed|null $data Data to send (encoded) with request
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function prepareStreamRequest(string $endpoint, array $data = []): \Psr\Http\Message\RequestInterface
    {
        return \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory()->createRequest('GET', $this->absoluteEndpoint($endpoint, $data));
    }

    /**
     * Prepare an HTTP request using a Guzzle client
     *
     * @param string $endpoint API endpoint
     * @param string $method Optional HTTP method
     * @param mixed|null $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function prepareRequest(string $endpoint, string $method = 'GET', mixed $data = null, array $headers = []): \GuzzleHttp\Psr7\Request
    {
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
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    protected function sendRequest(Request $request): \Psr\Http\Message\ResponseInterface
    {
        return $this->client($this->preparePlugins([new HeaderSetPlugin([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getApiKey(),
        ])]))->sendRequest($request);
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function sendStreamRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $options = [
            'use_file_buffer' => true,
            'memory_buffer_size' => 2097152,
        ];

        $plugins[] = new HeaderSetPlugin([
            'Content-Type' => 'application/octet-stream',
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Connection' => 'Keep-Alive'
        ]);
        $plugins[] = new DecoderPlugin(['use_content_encoding' => false]);
        $plugins[] = new ErrorPlugin();
        $plugins[] = new ResponseSeekableBodyPlugin($options);

        return $this->client($plugins, [
            'version' => 1.0,
            'synchronous' => true,
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => false,
                'protocols' => ['http', 'https'],
                'track_redirects' => false
            ],
            'expect' => true,
            'force_ip_resolve' => 'v4',
            'verify' => false,
            'stream' => true,
            'read_timeout' => 10
        ])->sendRequest($request);

    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function makeStreamRequest(string $endpoint, array $data = []): \Psr\Http\Message\ResponseInterface
    {
        $request = $this->prepareStreamRequest($endpoint, $data);
        return $this->sendStreamRequest($request);
    }

    /**
     * Helper method to automatically send a GET request and return the decoded response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
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
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    protected function makePostRequest(string $endpoint, array $data = [], array $headers = []): \Psr\Http\Message\ResponseInterface
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    protected function makePatchRequest(string $endpoint, array $data = [], array $headers = []): \Psr\Http\Message\ResponseInterface
    {
        $request = $this->prepareRequest($endpoint, 'PATCH', $data, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Return the appropriate API base uri based on connection mode
     *
     * @return string
     */
    protected function baseUri(): string
    {
        return $this->getApiEnvironment() == static::ENV_LIVE ? static::URL_LIVE : static::URL_PRACTICE;
    }

    /**
     * Return the appropriate API base uri based on connection mode
     *
     * @return string
     */
    protected function baseStreamUri(): string
    {
        return $this->getApiEnvironment() == static::ENV_LIVE ? static::URL_STREAM_LIVE : static::URL_STREAM_PRACTICE;
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
        return ($this->stream ? $this->baseStreamUri() : $this->baseUri())
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
     * @return array
     */
    protected function jsonDecode(string $data): array
    {
        return $this->KeysToSnake(json_decode($data, true));
    }

    /**
     * @param array $arr
     * @return array
     */
    protected function CamelToSnake(array $arr): array
    {
        $keys = array_keys($arr);
        foreach ($keys as &$key) {
            preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $key, $matches);
            $key = $matches[0];
            foreach ($key as &$match) {
                $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
            }
            $key = implode('_', $key);
        }
        return array_combine($keys, $arr);
    }

    /**
     * @param array $arr
     * @return array
     */
    protected function KeysToSnake(array $arr): array
    {
        if (isset($arr[0]) && is_array($arr[0])) {
            foreach ($arr as &$a) {
                $a = $this->KeysToSnake($a);
            }
        } else {
            $arr = $this->CamelToSnake($arr);
            foreach ($arr as &$a) {
                if (is_array($a)) {
                    $a = $this->KeysToSnake($a);
                }
            }
        }
        return $arr;
    }


    /**
     * Get all accounts for current token
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getAccounts(): array
    {
        return $this->makeGetRequest('/v3/accounts');
    }

    /**
     * Get full account details
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getAccount(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId());
    }

    /**
     * Get an account summary
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getAccountSummary(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/summary');
    }

    /**
     * Get a list of tradeable instruments for an account
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getAccountInstruments(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/instruments');
    }

    /**
     * Update the configurable properties of an account
     *
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function updateAccount(array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/configuration', $data);
    }

    /**
     * Get an account's changes to a particular account since a particular transaction id
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getAccountChanges(array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/changes', $data);
    }

    /**
     * Get candlestick data for an instrument
     *
     * @param string $instrumentName
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getInstrumentCandles(string $instrumentName, array $data = []): array
    {
        return $this->makeGetRequest('/v3/instruments/' . $instrumentName . '/candles', $data);
    }

    /**
     * Create an order for an account
     *
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function createOrder(array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePostRequest('/v3/accounts/' . $this->getAccountId() . '/orders', $data);
    }

    /**
     * Get a list of orders for an account
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getOrders(array $data = []): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/orders', $data);
    }

    /**
     * Get a list of pending orders for an account
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getPendingOrders(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/pendingOrders');
    }

    /**
     * Get details of an order
     *
     * @param string $orderSpecifier
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getOrder(string $orderSpecifier): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/orders/' . $orderSpecifier);
    }

    /**
     * Update an order by cancelling and replacing with a new one
     *
     * @param string $orderSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function updateOrder(string $orderSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/orders/' . $orderSpecifier, $data);
    }

    /**
     * Cancel a pending order
     *
     * @param string $orderSpecifier
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function cancelPendingOrder(string $orderSpecifier): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/orders/' . $orderSpecifier . '/cancel');
    }

    /**
     * Update Client Extensions for an order
     *
     * @param string $orderSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function updateOrderClientExtensions(string $orderSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/orders/' . $orderSpecifier . '/clientExtensions', $data);
    }

    /**
     * Get a list of trades for an account
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getTrades(array $data = []): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/trades', $data);
    }

    /**
     * Get a list of open trades for an account
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getOpenTrades(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/openTrades');
    }

    /**
     * Get details of a trade
     *
     * @param string $tradeSpecifier
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getTrade(string $tradeSpecifier): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/trades/' . $tradeSpecifier);
    }

    /**
     * Close (partially or fully) an open trade
     *
     * @param string $tradeSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function closeTrade(string $tradeSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/trades/' . $tradeSpecifier . '/close', $data);
    }

    /**
     * Update the Client Extensions for an open trade
     *
     * @param string $tradeSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function updateTradeClientExtensions(string $tradeSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/trades/' . $tradeSpecifier . '/clientExtensions', $data);
    }

    /**
     * Create, replace and cancel the dependent orders for an open trade
     *
     * @param string $tradeSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function updateTradeOrders(string $tradeSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/trades/' . $tradeSpecifier . '/orders', $data);
    }

    /**
     * Get a list of all positions for an account
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getPositions(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/positions');
    }

    /**
     * Get a list of all open positions for an account
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getOpenPositions(): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/openPositions');
    }

    /**
     * Get details of a single instrument's position in an account
     *
     * @param string $instrumentName
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getInstrumentPosition(string $instrumentName): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/positions/' . $instrumentName);
    }

    /**
     * Close a position on an account
     *
     * @param string $instrumentName
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function closePosition(string $instrumentName, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $this->getAccountId() . '/positions/' . $instrumentName . '/close', $data);
    }

    /**
     * Get a paginated list of all transactions on an account
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getTransactions(array $data = []): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/transactions', $data);
    }

    /**
     * Get a paginated list of all transactions on an account
     *
     * @param string $transactionId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getTransaction(string $transactionId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/transactions/' . $transactionId);
    }

    /**
     * Get a range of transactions on an account
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getTransactionRange(array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/transactions/idrange', $data);
    }

    /**
     * Get a range of transactions since (but not including) a particular id
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getTransactionsSince(array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/transactions/sinceid', $data);
    }

    /**
     * Get pricing information for a list of instruments on an account
     *
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Client\ClientExceptionInterface
     */
    public function getPricing(array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $this->getAccountId() . '/pricing', $data);
    }

    /**
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getPriceStream(array $data = []): \Psr\Http\Message\ResponseInterface
    {
        $this->stream = true;
        return $this->makeStreamRequest('/v3/accounts/' . $this->getAccountId() . '/pricing/stream', $data);
    }

    /**
     * Converts an stream into an string and returns the result. The position of
     * the pointer will not change if the stream is seekable. Note this copies
     * the complete content of the stream into the memory
     *
     * @param \Psr\Http\Message\StreamInterface $stream
     * @return string
     */
    public static function toString(\Psr\Http\Message\StreamInterface $stream): string
    {
        if (!$stream->isReadable()) {
            return '';
        }
        if ($stream->isSeekable()) {
            $pos = $stream->tell();
            if ($pos > 0) {
                $stream->seek(0);
            }
            $content = $stream->getContents();
            $stream->seek($pos);
        } else {
            $content = $stream->getContents();
        }
        return $content;
    }

}
