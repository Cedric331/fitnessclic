<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\CoachProfile;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Exercise;
use App\Models\Session;
use App\Observers\CustomerObserver;
use App\Policies\CategoryPolicy;
use App\Policies\CoachProfilePolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExercisePolicy;
use App\Policies\SessionPolicy;
use App\Services\ExerciseImageGeneratorService;
use OpenAI\Factory;
use OpenAI\Client;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Validation\Rules\Email;

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
        CoachProfile::class => CoachProfilePolicy::class,
        Conversation::class => ConversationPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return \OpenAI::client(config('services.openai.key'));
        });

        $this->app->singleton(ExerciseImageGeneratorService::class, function ($app) {
            return new ExerciseImageGeneratorService($app->make(Client::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Customer::observe(CustomerObserver::class);

        Email::defaults(function () {
            $rule = (new Email)->rfcCompliant();

            if (! app()->environment('testing')) {
                $rule->validateMxRecord();
            }

            return $rule;
        });
    }
}
