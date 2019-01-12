<?php

namespace Tests;

trait RegistersPackage
{
    protected function getPackageProviders($app)
    {
        return ['DarkGhostHunter\Passless\PasslessServiceProvider'];
    }
}