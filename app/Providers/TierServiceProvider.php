<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/tier.php', 'tier');
    }

    public function boot(): void
    {
        //
    }
}
