<?php

namespace Scrippy;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Scrippy\Commands\MakeScrippyCommand;
use Scrippy\Services\ScrippyExecutor;

class ScrippyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/scrippy.php',
            'scrippy'
        );
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeScrippyCommand::class,
            ]);
        }

        Event::listen(CommandFinished::class, function ($event) {
            if ($event->command === 'migrate') {
                app(ScrippyExecutor::class)->runPendingScripts();
            }
        });
    }
}
