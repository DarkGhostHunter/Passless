<?php

namespace DarkGhostHunter\Passless;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Notifications\Dispatcher;

class PasslessGuard extends SessionGuard
{

    /**
     * The name of the provider
     *
     * @var string
     */
    protected $providerName;

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $this->fireAttemptEvent($credentials, $remember);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        // We don't need to check if the User has the correct password, only if it exists,
        // so we will proceed if it was retrieved it. If it does not, then we can simply
        // bail out, and fire the Failed event. That's it, there is no much into it.
        if ($user) {

            // Get the intended route the user wanted to reach, as long the session handler
            // supports pulling this value from the session store. If that's not the case
            // we will just put a null in the intended variable for the notification.
            $intended = is_callable([$this->session, 'pull'])
                ? $this->session->pull('url.intended', null)
                : null;

            $this->sendLoginNotification($user, $remember, $intended);

            return true;
        }

        $this->fireFailedEvent($user, $credentials);

        return false;
    }

    /**
     * Send the Login notification
     *
     * @param Authenticatable $user
     * @param bool $remember
     * @param string $intended
     */
    public function sendLoginNotification(Authenticatable $user, bool $remember, string $intended = null)
    {
    	// Check first if the config has a notification class set.
		$notification = app('config')->get('passless.notification') ?? LoginNotification::class;

        $notification = app()->make($notification, [
            'remember' => $remember,
            'intended' => $intended
        ]);

        app(Dispatcher::class)->send($user, $notification);
    }

}
