<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Commands\CallsActivityCheck;
use RTippin\Messenger\Commands\InvitesCheck;
use RTippin\Messenger\Commands\ProvidersCache;
use RTippin\Messenger\Commands\ProvidersClear;
use RTippin\Messenger\Commands\Publish;
use RTippin\Messenger\Commands\PurgeDocuments;
use RTippin\Messenger\Commands\PurgeImages;
use RTippin\Messenger\Commands\PurgeMessages;
use RTippin\Messenger\Commands\PurgeThreads;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\VideoDriver;

class MessengerServiceProvider extends ServiceProvider
{
    use ChannelMap;
    use EventMap;
    use PolicyMap;
    use RouteMap;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerMiddleware();
        $this->configureRateLimiting();
        $this->registerPolicies();
        $this->registerEvents();
        $this->registerRoutes();
        $this->registerChannels();
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'messenger');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        $this->commands([
            CallsActivityCheck::class,
            InvitesCheck::class,
            ProvidersCache::class,
            ProvidersClear::class,
            Publish::class,
            PurgeDocuments::class,
            PurgeImages::class,
            PurgeMessages::class,
            PurgeThreads::class,
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/messenger.php' => config_path('messenger.php'),
        ], 'messenger.config');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/messenger'),
        ], 'messenger.views');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/messenger'),
        ], 'messenger.assets');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'messenger.migrations');

        if ($this->app['config']->get('messenger.calling.driver') === 'janus') {
            $this->publishes([
                __DIR__.'/../config/janus.php' => config_path('janus.php'),
            ], 'messenger.janus.config');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger.php', 'messenger');

        $this->app->singleton(
            Messenger::class,
            Messenger::class
        );
        $this->app->alias(
            Messenger::class,
            'messenger'
        );
        $this->app->singleton(
            FriendDriver::class,
            FriendBroker::class
        );
        $this->app->singleton(
            BroadcastDriver::class,
            $this->getBroadcastImplementation()
        );
        $this->app->singleton(
            VideoDriver::class,
            $this->getVideoImplementation()
        );
    }

    /**
     * Get the driver set in config for our services broadcasting feature.
     *
     * @return string
     */
    protected function getBroadcastImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.broadcasting.driver');

        return $this->app['config']->get('messenger.drivers.broadcasting')[$alias ?? 'null'];
    }

    /**
     * Get the driver set in config for our services video feature.
     *
     * @return string
     */
    protected function getVideoImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.calling.driver');

        return $this->app['config']->get('messenger.drivers.calling')[$alias ?? 'null'];
    }

    /**
     * Sanitize user defined middleware in case not array.
     *
     * @param $middleware
     * @return array
     */
    protected function mergeApiMiddleware($middleware): array
    {
        $merged = array_merge(['messenger.api'], is_array($middleware) ? $middleware : [$middleware]);

        array_push($merged, 'throttle:messenger-api');

        return $merged;
    }
}
