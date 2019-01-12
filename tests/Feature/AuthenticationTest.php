<?php

namespace Tests\Feature;

use DarkGhostHunter\Passless\LoginNotification;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Orchestra\Testbench\TestCase;
use Tests\RegistersPackage;

class AuthenticationTest extends TestCase
{
    use RegistersPackage, DatabaseMigrations;

    /** @var Controller */
    protected $class;

    protected function setUp()
    {
        parent::setUp();
        $this->loadLaravelMigrations();

        User::unguarded(function() {
            User::create([
                'name' => 'John Doe',
                'email' => 'test@email.com',
                'password' => '$2y$04$CYmtZUIIrviThh0Hd..aSOiTkOg9LA9eNu2daWj4IVT43wKnJagIS', // secret
                'email_verified_at' => now()->subDay()
            ]);
        });

    }

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
                'users' => [ 'driver' => 'eloquent', 'model' => User::class ],
            ],
            'passwords' => [
                'users' => [ 'provider' => 'users', 'table' => 'password_resets', 'expire' => 60, ],
            ]
        ]);

        $app['config']->set('database', [
            'default' => env('DB_CONNECTION', 'mysql'),
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => env('DB_DATABASE', database_path('database.sqlite')),
                    'prefix' => '',
                    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
                ],
            ],
            'migrations' => 'migrations',
        ]);

        $app->make(\Illuminate\Contracts\Console\Kernel::class)
            ->call('make:auth', ['--views' => true, '--force' => true]);

        /** @var \Illuminate\Contracts\Routing\Registrar $registrar */
        $registrar = $app->make(\Illuminate\Contracts\Routing\Registrar::class);

        $this->routeLogin($registrar);
    }

    /** @param \Illuminate\Contracts\Routing\Registrar $registrar */
    protected function routeLogin($registrar)
    {
        $registrar->post('login', function (Request $request) {
            /** @var \Illuminate\Auth\AuthManager $manager */
            $manager = $this->app->make(\Illuminate\Auth\AuthManager::class);

            /** @var \DarkGhostHunter\Passless\PasslessGuard $passless */
            $passless = $manager->guard('web');

            $attempt = $passless->attempt(
                $request->only('email'),
                $request->get('remember')
            );

            if ($attempt) {
                return 'true';
            }

            return 'false';

        })->name('login');
    }


    public function testAuthententicatesFails()
    {
        $event = \Mockery::mock(Dispatcher::class);

        \Auth::guard('web')->setDispatcher($event);

        $event->expects('dispatch')->with(\Mockery::type(Attempting::class));
        $event->expects('dispatch')->with(\Mockery::type(Failed::class));
        $event->shouldNotReceive('dispatch')->with(\Mockery::type(LoginNotification::class));

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->post('login', [
            'email' => 'noUserEmail@email.com',
            'remember' => true
        ])->assertSee('false');
    }

    public function testAuthenticatesCompletely()
    {
        \Notification::fake();

        $user = User::first();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $event = \Mockery::mock(Dispatcher::class);

        $event->expects('dispatch')->with(\Mockery::type(Attempting::class));
        $event->expects('dispatch')->with(\Mockery::type(Login::class));
        $event->expects('dispatch')->with(\Mockery::type(Authenticated::class));

        $cookie = \Mockery::mock(\Illuminate\Contracts\Cookie\QueueingFactory::class);
        $cookie->expects('forever')->andReturnTrue();
        $cookie->expects('queue')->andReturnTrue();

        \Auth::guard('web')->setCookieJar($cookie);
        \Auth::guard('web')->setDispatcher($event);

        $url = '';

        $this->post('login', $array = [
            'email' => $user->email,
            'remember' => true
        ])->assertSee('true');

        \Notification::assertSentTo(
            $user,
            LoginNotification::class,
            function ($notification, $channels) use ($user, &$url) {
                /** @var LoginNotification $notification */
                $url = $notification->createLoginUrl($user, 5);
                return true;
            }
        );

        $url = parse_url($url);

        $this->get(trim($url['path'], '/').'?'.$url['query'])
            ->assertRedirect('http://localhost/home');
    }

}