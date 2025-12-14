<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Exercise;
use App\Models\Session;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExercisePolicy;
use App\Policies\SessionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Exercise::class => ExercisePolicy::class,
        Customer::class => CustomerPolicy::class,
        Category::class => CategoryPolicy::class,
        Session::class => SessionPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
