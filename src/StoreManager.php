<?php

namespace BwtTeam\LaravelRequestLogger;

use BwtTeam\LaravelRequestLogger\Stores\Database as DatabaseStore;
use Illuminate\Support\Manager;

class StoreManager extends Manager
{
    /**
     * Get the default request logger store driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['request-logger.default'];
    }

    /**
     * Set the default request logger store driver name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setDefaultDriver(string $name)
    {
        $this->app['config']['request-logger.default'] = $name;
    }

    /**
     * Create an instance of the database store driver.
     *
     * @return \BwtTeam\LaravelRequestLogger\Stores\StoreInterface
     */
    protected function createDatabaseDriver()
    {
        $table = $this->app['config']['request-logger.stores.database.table'];
        $connection = $this->app['config']['request-logger.stores.database.connection'];

        return new DatabaseStore($this->app['db']->connection($connection), $table);
    }
}