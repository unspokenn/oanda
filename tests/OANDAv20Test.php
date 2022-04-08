<?php
declare(strict_types=1);

namespace Unspokenn\OANDA\Tests;

use PHPUnit\Framework\TestCase;
use Unspokenn\OANDA\OANDAv20;

/**
 *
 */
class OANDAv20Test extends TestCase
{
    protected string $apiKey = '123456-7890';
    protected int $apiEnvironment = OANDAv20::ENV_PRACTICE;

    /**
     * @return void
     */
    public function testCanBeInstantiated()
    {
        static::assertInstanceOf(
            expected: OANDAv20::class,
            actual: new OANDAv20()
        );
    }

    /**
     * @return void
     */
    public function testCanBeInstantiatedWithArguments()
    {
        static::assertInstanceOf(
            OANDAv20::class,
            new OANDAv20($this->apiEnvironment, $this->apiKey)
        );
    }

    /**
     * @return void
     */
    public function testSetAndGetApiEnvironment()
    {
        $oanda = new OANDAv20();

        static::assertInstanceOf(
            OANDAv20::class,
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
        $oanda = new OANDAv20();

        static::assertInstanceOf(
            OANDAv20::class,
            $oanda->setApiKey($this->apiKey)
        );

        static::assertEquals(
            $oanda->getApiKey(),
            $this->apiKey
        );
    }
}
