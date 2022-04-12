<?php
/**
 * Laravel Oanda Client
 *
 * @author    Sadık Ergüven <unspoken598@gmail.com>
 * @copyright 2022 Sadık Ergüven
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      https://github.com/unspokenn/oanda-client
 */

namespace Unspokenn\Oanda;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;


class OandaServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/oanda.php' => config_path('oanda.php')], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/oanda.php', 'oanda');

        $this->app->singleton(Oanda::class, function ($app) {
            return new Oanda();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['oanda'];
    }
}
