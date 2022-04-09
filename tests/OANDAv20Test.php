<?php
declare(strict_types=1);

namespace Unspokenn\Oanda\Tests;

use PHPUnit\Framework\TestCase;
use Unspokenn\Oanda\Client;

/**
 *
 */
class OANDAv20Test extends TestCase
{
    protected string $apiKey = '123456-7890';
    protected int $apiEnvironment = Client::ENV_PRACTICE;

    /**
     * @return void
     */
    public function testCanBeInstantiated()
    {
        static::assertInstanceOf(
            expected: Client::class,
            actual: new Client()
        );
    }

    /**
     * @return void
     */
    public function testCanBeInstantiatedWithArguments()
    {
        static::assertInstanceOf(
            Client::class,
            new Client($this->apiEnvironment, $this->apiKey)
        );
    }

    /**
     * @return void
     */
    public function testSetAndGetApiEnvironment()
    {
        $oanda = new Client();

        static::assertInstanceOf(
            Client::class,
            $oanda->setApiEnvironment($this->apiEnvironment)
        );

        static::assertEquals(
            $oanda->getApiEnvironment(),
            $this->apiEnvironment
        );
    }

    /**
     * @return void
     */
    public function testSetAndGetApiKey()
    {
        $oanda = new Client();

        static::assertInstanceOf(
            Client::class,
            $oanda->setApiKey($this->apiKey)
        );

        static::assertEquals(
            $oanda->getApiKey(),
            $this->apiKey
        );
    }
}
