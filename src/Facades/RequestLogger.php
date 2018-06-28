<?php

namespace BwtTeam\LaravelRequestLogger\Facades;

use BwtTeam\LaravelRequestLogger\RepositoryInterface;
use Illuminate\Support\Facades\Facade;

class RequestLogger extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return RepositoryInterface::class;
    }
}