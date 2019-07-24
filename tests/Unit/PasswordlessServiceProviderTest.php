<?php

namespace Tests\Unit;

use DarkGhostHunter\Passless\PasslessGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use Tests\RegistersPackage;
use DarkGhostHunter\Passless\PasslessServiceProvider;

class PasswordlessServiceProviderTest extends TestCase
{
    use RegistersPackage;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth', [
            'defaults' => [ 'guard' => 'web', 'passwords' => 'users', ],
            'guards' => [
                'web' => ['driver' => 'passless', 'provider' => 'users'],
                'api' => ['driver' => 'token', 'provider' => 'users'],
            ],
            'providers' => [
                'users' => [ 'driver' => 'eloquent', 'model' => \Illuminate\Foundation\Auth\User::class ],
            ],
            'passwords' => [
                'users' => [ 'provider' => 'users', 'table' => 'password_resets', 'expire' => 60, ],
            ]
        ]);
    }

    public function testExtendsAuth()
    {
        $this->assertInstanceOf(
            PasslessGuard::class,
            $guard = $this->app->make(Factory::class)->guard('web')
        );
    }

    public function testRegisterRoutes()
    {
        /** @var \Illuminate\Contracts\Routing\UrlGenerator $router */
        $router = $this->app->make(\Illuminate\Contracts\Routing\UrlGenerator::class);

        $this->assertNotFalse(
            filter_var($router->route('passless.login'), FILTER_VALIDATE_URL)
        );
    }

    public function testMergesConfig()
    {
        $config = include __DIR__ . '/../../config/passless.php';

        $this->assertEquals($this->app->make('config')->get('passless'), $config);
    }

    public function testPublishesConfig()
    {
        $file = app()->configPath('passless.php');

        if (file_exists($file)) {
            unlink($file);
        }

        Artisan::call('vendor:publish', [
            '--provider' => PasslessServiceProvider::class
        ]);

        $this->assertFileEquals(__DIR__ . '/../../config/passless.php', $file);

        unlink($file);
    }

}
