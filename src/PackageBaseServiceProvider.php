<?php

namespace Bakgul\PackageBase;

use Illuminate\Support\ServiceProvider;

class PackageBaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            \Bakgul\PackageBase\Commands\SetPackageBaseCommand::class,
        ]);
    }

    public function register()
    {
        //
    }
}
