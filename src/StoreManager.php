<?php

namespace BwtTeam\LaravelRequestLogger;

use BwtTeam\LaravelRequestLogger\Stores\Database as DatabaseStore;
use BwtTeam\LaravelRequestLogger\Stores\StoreInterface;
use Illuminate\Support\Manager;

class StoreManager extends Manager
{
    /**
     * Get the default request logger store driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->container['config']['request-logger.default'];
    }

    /**
     * Set the default request logger store driver name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        $this->container['config']['request-logger.default'] = $name;
    }

    /**
     * Create an instance of the database store driver.
     *
     * @return StoreInterface
     */
    protected function createDatabaseDriver(): StoreInterface
    {
        $table = $this->container['config']['request-logger.stores.database.table'];
        $connection = $this->container['config']['request-logger.stores.database.connection'];

        return new DatabaseStore($this->container['db']->connection($connection), $table);
    }
}