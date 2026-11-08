<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Document;
use App\Models\MonthlySubmission;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\MonthlySubmissionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(Department::class, DepartmentPolicy::class);

        Gate::policy(User::class, UserPolicy::class);

        Gate::policy(
            MonthlySubmission::class,
            MonthlySubmissionPolicy::class
        );

        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
