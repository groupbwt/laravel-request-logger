<?php
declare(strict_types=1);

namespace BwtTeam\LaravelRequestLogger\Stores;

interface StoreInterface
{
    /**
     * Save the request logs to storage.
     *
     * @param array $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function save(array $data): bool;
}