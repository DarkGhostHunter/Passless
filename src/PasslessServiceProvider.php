<?php

namespace DarkGhostHunter\Passless;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\ServiceProvider;

class PasslessServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/passless.php', 'passless'
        );

        $this->app->resolving(AuthFactory::class, function ($auth) {
            /** @var \Illuminate\Auth\AuthManager $auth */
            $auth->extend('passless', function ($app, $name, array $config) use ($auth) {
                /** @var \Illuminate\Foundation\Application $app */
                $guard = $app->make(PasslessGuard::class, [
                    'name' => $name,
                    'provider' => $auth->createUserProvider($config['provider'])
                ]);

                return $guard;
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
        $this->publishes([
            __DIR__ . '/../config/passless.php' => config_path('passless.php'),
        ]);
    }
}
