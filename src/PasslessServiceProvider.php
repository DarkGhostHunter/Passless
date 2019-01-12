<?php

namespace DarkGhostHunter\Passless;

use Illuminate\Config\Repository as Config;
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
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootGuardDriver();
        $this->bootRoute();
    }

    /**
     * Boots the Passless Guard Driver
     *
     * @return void
     */
    public function bootGuardDriver()
    {
        /** @var \Illuminate\Auth\AuthManager $auth */
        $auth = $this->app->make(AuthFactory::class);

        $auth->extend('passless', function ($app, $name, array $config) use ($auth) {
            /** @var \Illuminate\Foundation\Application $app */
            $guard = $app->make(PasslessGuard::class, [
                'name' => $name,
                'provider' => $auth->createUserProvider($config['provider'])
            ]);

            return $guard;
        });
    }
    /**
     * Registers the default login route
     *
     * @return void
     */
    protected function bootRoute()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
    }
}