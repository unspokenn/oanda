<?php
declare(strict_types=1);

namespace Unspokenn\Oanda\Tests;

use PHPUnit\Framework\TestCase;
use Unspokenn\Oanda\Oanda;

/**
 *
 */
class OANDAv20Test extends TestCase
{
    protected string $apiKey = '123456-7890';
    protected int $apiEnvironment = Oanda::ENV_PRACTICE;

    /**
     * @return void
     */
    public function testCanBeInstantiated()
    {
        static::assertInstanceOf(
            expected: Oanda::class,
            actual: new Oanda()
        );
    }

    /**
     * @return void
     */
    public function testCanBeInstantiatedWithArguments()
    {
        static::assertInstanceOf(
            Oanda::class,
            new Oanda($this->apiEnvironment, $this->apiKey)
        );
    }

    /**
     * @return void
     */
    public function testSetAndGetApiEnvironment()
    {
        $oanda = new Oanda();

        static::assertInstanceOf(
            Oanda::class,
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
        $oanda = new Oanda();

        static::assertInstanceOf(
            Oanda::class,
            $oanda->setApiKey($this->apiKey)
        );

        static::assertEquals(
            $oanda->getApiKey(),
            $this->apiKey
        );
    }
}
