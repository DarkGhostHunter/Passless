<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

// The Passless login route
Route::get(Config::get('passless.login.path', 'passless/login'))
    ->name(Config::get('passless.login.name', 'passless.login'))
    ->uses(Config::get('passless.login.action', 'DarkGhostHunter\Passless\LoginController@login'));
