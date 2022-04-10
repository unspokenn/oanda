<?php

namespace Unspokenn\Oanda\Tests;

use PHPUnit\Framework\TestCase;
use Unspokenn\Oanda\Oanda;

class OandaTest extends TestCase
{
    /**
     * @var array|mixed
     */
    protected array $config;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = require __DIR__ . '/../config/oanda.php';
    }

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(
            expected: Oanda::class,
            actual: new Oanda($this->config)
        );
    }

    public function testCanBeInstantiatedWithArguments()
    {
        $this->assertInstanceOf(
            Oanda::class,
            new Oanda($this->config)
        );
    }

    public function testSetAndGetApiEnvironment()
    {
        $oanda = new Oanda($this->config);

        $this->assertInstanceOf(
            Oanda::class,
            $oanda->setApiEnvironment($this->config['environment'])
        );

        $this->assertEquals(
            $oanda->getApiEnvironment(),
            $this->config['environment']
        );
    }

    public function testSetAndGetApiKey()
    {
        $oanda = new Oanda($this->config);

        $this->assertInstanceOf(
            Oanda::class,
            $oanda->setApiKey($this->config['api_key']['key'])
        );

        $this->assertEquals(
            $oanda->getApiKey(),
            $this->config['api_key']['key']
        );
    }
}
