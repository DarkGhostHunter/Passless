<?php

namespace DarkGhostHunter\Passless;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class LoginController extends Controller
{
    /**
     * Our Passless Guard
     *
     * @var PasslessGuard
     */
    protected $guard;

    /**
     * The View factory
     *
     * @var View
     */
    protected $view;

    /**
     * The Config Repository
     *
     * @var string
     */
    protected $config;

    /**
     * Create a new controller instance.
     *
     * @param Auth $auth
     * @param Config $config
     * @param View $view
     */
    public function __construct(Auth $auth, Config $config, View $view)
    {
        $this->view = $view;
        $this->config = $config;
        $this->guard = $auth->guard($this->config->get('passless.login.guard'));

        $middleware = Arr::wrap($this->config->get('passless.login.middleware'));

        $this->setLoginMiddleware($middleware);
    }

    /**
     * Set the Login middleware
     *
     * @param array $middleware
     * @return void
     */
    protected function setLoginMiddleware(array $middleware)
    {
        $middleware = array_merge(['signed', 'guest'], $middleware);

        $this->middleware($middleware);
    }

    /**
     * Attempt to Log in the user from the email
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // We will ask our guard to log in the user first by retrieving him by
        // the identifier of the query URL, as long he exists. Even if not,
        // we will redirect the user to the intended URL or the default.
        if ($user = $this->guard->getProvider()->retrieveById($request->query('id'))) {
            $this->guard->login($user, (bool)$request->query('remember'));
        }

        return app('redirect')->to(
            $request->query('intended') ?? $this->config->get('passless.redirectTo',  '/home'),
            302, [],null
        );
    }

}
