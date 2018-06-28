<?php
declare(strict_types=1);

namespace BwtTeam\LaravelRequestLogger;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface RepositoryInterface
{
    /**
     * Get the value of the log's primary key.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Put an extra item in the logger repository by key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function put(string $key, $value): void;

    /**
     * Get an extra item from the logger repository by key.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Get extra data from the logger repository.
     *
     * @return array
     */
    public function extra(): array;

    /**
     * Remove an item from the logger repository.
     *
     * @param string $key
     *
     * @return void
     */
    public function forget(string $key): void;

    /**
     * Remove all items from the logger repository.
     *
     * @return void
     */
    public function flush(): void;

    /**
     * Get all of the items in the logger repository.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Collects data for the given Request and Response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return RepositoryInterface
     */
    public function process(Request $request, Response $response): RepositoryInterface;

    /**
     * Save the request logs to storage.
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * Checks whether the request logger is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool;
}