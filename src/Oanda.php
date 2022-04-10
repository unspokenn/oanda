<?php
declare(strict_types=1);

namespace Unspokenn\Oanda;

use GuzzleHttp\{Psr7\Response};
use Http\Client\Common\HttpClientPool\LeastUsedClientPool;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\ResponseInterface;

/**
 * Oanda V20 REST API(v3)
 */
class Oanda
{
    /**
     * Defines the LIVE API url
     *
     * @const URL_LIVE
     */
    public const Production = 'https://api-fxtrade.oanda.com';

    /**
     * Defines the PRACTICE API url
     *
     * @const URL_PRACTICE
     */
    public const Demo = 'https://api-fxpractice.oanda.com';

    /**
     * API key for current connection
     *
     * @var string
     */
    public string $apiKey;

    /**
     * Build an OANDA v20 API instance
     *
     * @param \Illuminate\Config\Repository $config
     * @param string|null $apiKey Optional API key to set at instantiation
     * @return void
     */
    public function __construct(/*ConfigRepository */ $config, bool $production = false)
    {
        $httpClientPool = new LeastUsedClientPool();

        if(isset($config['api_keys'])) {
            foreach ($config['api_keys'] as $data) {
                $httpClientPool->addHttpClient(HttpClientFactory::create($data['key']));
            }
        }

//        $messageFactory = MessageFactoryDiscovery::find();
//
//        $httpClient = HttpClientDiscovery::find();
//        $httpAsyncClient = HttpAsyncClientDiscovery::find();
//
//        $httpClientPool = new LeastUsedClientPool();
//        $httpClientPool->addHttpClient($httpClient);
//        $httpClientPool->addHttpClient($httpAsyncClient);
//
//        $this->baseUrl = $production ? static::Production : static::Demo;
//        foreach ($api_keys as $api_key)
//        {
//
//        }
//        $this->withBearerAuth();
//
//        if ($apiEnvironment !== null) {
//            $this->setApiEnvironment($apiEnvironment);
//        }
//
//        if ($apiKey !== null) {
//            $this->setApiKey($apiKey);
//        }
    }

    /**
     * Return the current API environment
     *
     * @return bool
     */
    public function getProduction(): bool
    {
        return $this->production;
    }


    /**
     *  Set the API environment
     *
     * @param bool $production
     * @return $this
     */
    public function setProduction(bool $production): static
    {
        $this->production = $production;

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
     * Return the appropriate API base uri based on connection mode
     *
     * @return string
     */
    #[Pure] protected function baseUri(): string
    {
        return $this->getProduction() ? static::Production : static::Demo;
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
     * Get a list of trade able instruments for an account
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
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateAccount(string $accountId, array $data): Response|ResponseInterface
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
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder(string $accountId, array $data): Response|ResponseInterface
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
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateOrder(string $accountId, string $orderSpecifier, array $data): Response|ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier, $data);
    }

    /**
     * Cancel a pending order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelPendingOrder(string $accountId, string $orderSpecifier): Response|ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier . '/cancel');
    }

    /**
     * Update Client Extensions for an order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @param array $data
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateOrderClientExtensions(string $accountId, string $orderSpecifier, array $data): Response|ResponseInterface
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
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function closeTrade(string $accountId, string $tradeSpecifier, array $data): Response|ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/close', $data);
    }

    /**
     * Update the Client Extensions for an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTradeClientExtensions(string $accountId, string $tradeSpecifier, array $data): Response|ResponseInterface
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/clientExtensions', $data);
    }

    /**
     * Create, replace and cancel the dependent orders for an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTradeOrders(string $accountId, string $tradeSpecifier, array $data): Response|ResponseInterface
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
     * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function closePosition(string $accountId, string $instrumentName, array $data): Response|ResponseInterface
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
}
