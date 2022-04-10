<?php

namespace Unspokenn\Oanda\Tests;

use Unspokenn\Oanda\Oanda;

class OandaTest extends \PHPUnit\Framework\TestCase
{
    protected string $apiKey = '123456-7890';
    protected int $apiEnvironment = Oanda::ENV_PRACTICE;

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(
            expected: Oanda::class,
            actual: new Oanda()
        );
    }

    public function testCanBeInstantiatedWithArguments()
    {
        $this->assertInstanceOf(
            Oanda::class,
            new Oanda($this->apiEnvironment, $this->apiKey)
        );
    }

    public function testSetAndGetApiEnvironment()
    {
        $oanda = new Oanda;

        $this->assertInstanceOf(
            Oanda::class,
            $oanda->setApiEnvironment($this->apiEnvironment)
        );

        $this->assertEquals(
            $oanda->getApiEnvironment(),
            $this->apiEnvironment
        );
    }

    public function testSetAndGetApiKey()
    {
        $oanda = new Oanda;

        $this->assertInstanceOf(
            Oanda::class,
            $oanda->setApiKey($this->apiKey)
        );

        $this->assertEquals(
            $oanda->getApiKey(),
            $this->apiKey
        );
    }
}
