<?php

namespace KPAPH\MSG4wrdIO;

use Illuminate\Support\ServiceProvider;

class MSG4wrdIOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/msg4wrdio.php',
            'msg4wrdio'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'msg4wrd-io');

        if (config('msg4wrdio.expose_demo_routes', false)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/msg4wrdio.php' => config_path('msg4wrdio.php'),
            ], 'msg4wrdio-config');
        }
    }
}
