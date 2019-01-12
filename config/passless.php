<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    |
    | For convenience we include a simple Login Notification message to send
    | when the credentials are correct. You can change this to a different
    | notification of your choosing, as this only uses little markdown.
    |
    */

    'notification' => \DarkGhostHunter\Passless\LoginNotification::class,

    /*
    |--------------------------------------------------------------------------
    | Link lifetime
    |--------------------------------------------------------------------------
    |
    | The notification includes a temporary signed URL with a lifetime. If the
    | user doesn't access the URL in that time, it will be redirected to the
    | login page to try again. Five minutes it's enough, most of the time.
    |
    */

    'lifetime' => 5,

    /*
    |--------------------------------------------------------------------------
    | Login Route properties
    |--------------------------------------------------------------------------
    |
    | This is the route that logs in the User after he clicks his email. Here
    | you have some settings to modify like the path, name, middleware and
    | action, just to conveniently save you time when doing adjustments.
    |
    | Since the routes in your application may be cached, as well this config,
    | it is recommended to clear both caches if you made a change to these
    | values, because otherwise the route will keep using the old config.
    |
    */

    'login' => [
        'path' => 'passless/login',
        'name' => 'passless.login',
        'middleware' => ['web'],
        'action' => 'DarkGhostHunter\Passless\LoginController@login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Redirection
    |--------------------------------------------------------------------------
    |
    | When the user logs in successfully, they will redirected to the home path
    | of your application, or any other you specify, if the login link didn't
    | include the intended path from the initial login request in the app.
    |
    */

    'redirectTo' => '/home',

];