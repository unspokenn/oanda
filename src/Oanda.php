<?php
declare(strict_types=1);

namespace Unspokenn\Oanda;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Oanda
{
    /**
     * Defines the LIVE API url
     *
     * @const URL_LIVE
     */
    const URL_LIVE = 'https://api-fxtrade.oanda.com';

    /**
     * Defines the PRACTICE API url
     *
     * @const URL_PRACTICE
     */
    const URL_PRACTICE = 'https://api-fxpractice.oanda.com';

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
    protected string $accountId;

    /**
     * Build an OANDA API instance
     *
     * @param \Illuminate\Config\Repository $config
     */
    public function __construct($config)
    {
        $this->setApiEnvironment($config->get('oanda.environment'));
        $this->setApiKey($config->get('oanda.api_key.key'));
        $this->setAccountId($config->get('oanda.api_key.account'));
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
        $this->apiEnvironment = $apiEnvironment;

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
     * Prepare an HTTP request using a Guzzle client
     *
     * @param string $endpoint API endpoint
     * @param string $method Optional HTTP method
     * @param mixed|null $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return \GuzzleHttp\Psr7\Request
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendRequest(Request $request): \Psr\Http\Message\ResponseInterface
    {
        $client = new Client;

        return $client->send($request);
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * Return the bearer token from the current API key
     *
     * @return string
     */
    protected function bearerToken(): string
    {
        return 'Bearer ' . $this->getApiKey();
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

    /**
     * Get all accounts for current token
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccounts(): array
    {
        return $this->makeGetRequest('/v3/accounts');
    }

    /**
     * Get full account details
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccount(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId);
    }

    /**
     * Get an account summary
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccountSummary(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/summary');
    }

    /**
     * Get a list of tradeable instruments for an account
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccountInstruments(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/instruments');
    }

    /**
     * Update the configurable properties of an account
     *
     * @param string $accountId
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateAccount(string $accountId, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/configuration', $data);
    }

    /**
     * Get an account's changes to a particular account since a particular transaction id
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccountChanges(string $accountId, array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/changes', $data);
    }

    /**
     * Get candlestick data for an instrument
     *
     * @param string $instrumentName
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInstrumentCandles(string $instrumentName, array $data = []): array
    {
        return $this->makeGetRequest('/v3/instruments/' . $instrumentName . '/candles', $data);
    }

    /**
     * Create an order for an account
     *
     * @param string $accountId
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder(string $accountId, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePostRequest('/v3/accounts/' . $accountId . '/orders', $data);
    }

    /**
     * Get a list of orders for an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrders(string $accountId, array $data = []): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/orders', $data);
    }

    /**
     * Get a list of pending orders for an account
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPendingOrders(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/pendingOrders');
    }

    /**
     * Get details of an order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrder(string $accountId, string $orderSpecifier): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier);
    }

    /**
     * Update an order by cancelling and replacing with a new one
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateOrder(string $accountId, string $orderSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier, $data);
    }

    /**
     * Cancel a pending order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelPendingOrder(string $accountId, string $orderSpecifier): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier . '/cancel');
    }

    /**
     * Update Client Extensions for an order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateOrderClientExtensions(string $accountId, string $orderSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier . '/clientExtensions', $data);
    }

    /**
     * Get a list of trades for an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTrades(string $accountId, array $data = []): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/trades', $data);
    }

    /**
     * Get a list of open trades for an account
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOpenTrades(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/openTrades');
    }

    /**
     * Get details of a trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTrade(string $accountId, string $tradeSpecifier): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier);
    }

    /**
     * Close (partially or fully) an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function closeTrade(string $accountId, string $tradeSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/close', $data);
    }

    /**
     * Update the Client Extensions for an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTradeClientExtensions(string $accountId, string $tradeSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/clientExtensions', $data);
    }

    /**
     * Create, replace and cancel the dependent orders for an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTradeOrders(string $accountId, string $tradeSpecifier, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/orders', $data);
    }

    /**
     * Get a list of all positions for an account
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPositions(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/positions');
    }

    /**
     * Get a list of all open positions for an account
     *
     * @param string $accountId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOpenPositions(string $accountId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/openPositions');
    }

    /**
     * Get details of a single instrument's position in an account
     *
     * @param string $accountId
     * @param string $instrumentName
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInstrumentPosition(string $accountId, string $instrumentName): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/positions/' . $instrumentName);
    }

    /**
     * Close a position on an account
     *
     * @param string $accountId
     * @param string $instrumentName
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function closePosition(string $accountId, string $instrumentName, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/positions/' . $instrumentName . '/close', $data);
    }

    /**
     * Get a paginated list of all transactions on an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactions(string $accountId, array $data = []): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions', $data);
    }

    /**
     * Get a paginated list of all transactions on an account
     *
     * @param string $accountId
     * @param string $transactionId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransaction(string $accountId, string $transactionId): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions/' . $transactionId);
    }

    /**
     * Get a range of transactions on an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactionRange(string $accountId, array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions/idrange', $data);
    }

    /**
     * Get a range of transactions since (but not including) a particular id
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactionsSince(string $accountId, array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions/sinceid', $data);
    }

    /**
     * Get pricing information for a list of instruments on an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPricing(string $accountId, array $data): array
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/pricing', $data);
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }


    /**
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        return $this->$method(...$parameters);
    }
}
