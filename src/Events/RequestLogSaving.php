<?php
declare(strict_types=1);

namespace BwtTeam\LaravelRequestLogger\Events;

use BwtTeam\LaravelRequestLogger\RepositoryInterface as RequestLoggingRepository;

class RequestLogSaving
{
    /**
     * The request logging repository instance.
     *
     * @var RequestLoggingRepository
     */
    public $repository;

    /**
     * Create a new event instance.
     *
     * @param \BwtTeam\LaravelRequestLogger\RepositoryInterface $repository
     *
     */
    public function __construct(RequestLoggingRepository $repository)
    {
        $this->repository = $repository;
    }
}
