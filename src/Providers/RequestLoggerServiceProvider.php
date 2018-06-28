<?php

namespace BwtTeam\LaravelRequestLogger\Providers;

use BwtTeam\LaravelRequestLogger\Console\TableCommand;
use BwtTeam\LaravelRequestLogger\RepositoryInterface;
use BwtTeam\LaravelRequestLogger\RequestLoggerRepository;
use BwtTeam\LaravelRequestLogger\StoreManager;
use BwtTeam\LaravelRequestLogger\Stores\StoreInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use BwtTeam\LaravelRequestLogger\Middleware\RequestLogger as RequestLoggingMiddleware;

class RequestLoggerServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'RequestLoggerTable' => 'command.request-logger.table',
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();

        $this->app->make(HttpKernel::class)->pushMiddleware(RequestLoggingMiddleware::class);
    }

    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        $configPath = __DIR__ . '/../../config/request-logger.php';

        if (function_exists('config_path')) {
            $publishPath = config_path('request-logger.php');
        } else {
            $publishPath = base_path('config/request-logger.php');
        }

        $this->publishes([$configPath => $publishPath], 'config');
        $this->mergeConfigFrom($configPath, 'request-logger');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRequestLogger();

        $this->registerCommands(array_merge(
            $this->commands, $this->devCommands
        ));
    }

    /**
     * Register the given commands.
     *
     * @param array $commands
     *
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}Command"], []);
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRequestLoggerTableCommand()
    {
        $this->app->singleton('command.request-logger.table', function ($app) {
            return new TableCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Register the request logger.
     */
    protected function registerRequestLogger()
    {
        $this->app->singleton('request_logger', function ($app) {
            return new StoreManager($app);
        });

        $this->app->singleton(StoreInterface::class, function ($app) {
            return $app['request_logger']->driver();
        });
        $this->app->singleton(
            RepositoryInterface::class, RequestLoggerRepository::class
        );

        $this->app->alias(
            RequestLoggerRepository::class, 'request_logger.store'
        );
        $this->app->alias(
            RequestLoggerRepository::class, 'request_logger.repository'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
