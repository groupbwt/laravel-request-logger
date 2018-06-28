<?php

namespace BwtTeam\LaravelRequestLogger\Middleware;

use BwtTeam\LaravelRequestLogger\RepositoryInterface as RequestLoggingRepository;

class RequestLogger
{
    /**
     * @var RequestLoggingRepository
     */
    protected $repository;

    /**
     * Log constructor.
     *
     * @param RequestLoggingRepository $repository
     */
    public function __construct(RequestLoggingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     *
     * @return void
     */
    public function terminate($request, $response)
    {
        if ($this->repository->isEnabled()) {
            $this->repository
                ->process($request, $response)
                ->save();
        }
    }
}
