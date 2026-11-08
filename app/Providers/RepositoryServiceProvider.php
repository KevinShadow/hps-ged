<?php

namespace App\Providers;

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Contracts\Repositories\MonthlySubmissionRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\Eloquent\DepartmentRepository;
use App\Repositories\Eloquent\DocumentRepository;
use App\Repositories\Eloquent\MonthlySubmissionRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            MonthlySubmissionRepositoryInterface::class,
            MonthlySubmissionRepository::class
        );

        $this->app->bind(
            DocumentRepositoryInterface::class,
            DocumentRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
