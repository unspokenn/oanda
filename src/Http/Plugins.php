<?php
declare(strict_types=1);

namespace Unspokenn\Oanda\Http;
/**
 * Abstract Class Base
 *
 * @package Unspokenn\Oanda\Http\Base
 *
 * @method static \Http\Client\Common\Plugin\ContentTypePlugin detectContentType($config = ['skip_detection' => false, 'size_limit' => 16000000])
 * @method static \Unspokenn\Oanda\Http\Client addPlugin()
 * @method static \Unspokenn\Oanda\Http\Client withDefaultPlugins()
 */
class Plugins
{
    protected array $plugins = [
        'setQueryPram' => \Http\Client\Common\Plugin\QueryDefaultsPlugin::class,
        'detectContentType' => \Http\Client\Common\Plugin\ContentTypePlugin::class,
    ];
    private array $map = [

    ];

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if (array_key_exists($method, $this->map)) {
            call_user_func([$this->plugin[$method], 'method'], ...$parameters);

        }

//        return $this->$method(...$parameters);
        return $this;
    }
}
