<?php

namespace App\Providers;

use App\Services\AI\ClaudeProvider;
use App\Services\AI\ComplianceRiskEngine;
use App\Services\AI\GmuAiService;
use App\Services\AI\GrantMatchingEngine;
use App\Services\AI\LlmInterface;
use App\Services\AI\NullProvider;
use App\Services\Sms\AfricasTalkingGateway;
use App\Services\Sms\NullGateway;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the LLM interface to Claude if key is set, otherwise NullProvider
        $this->app->bind(LlmInterface::class, function () {
            $provider = config('services.anthropic.api_key') ? ClaudeProvider::class : NullProvider::class;
            return new $provider();
        });

        // SMS gateway, bound the same way as the LLM provider: a real driver
        // when it is configured, a logging null driver until then.
        $this->app->bind(SmsGateway::class, function () {
            return config('services.sms.gateway') === 'africastalking'
                ? new AfricasTalkingGateway()
                : new NullGateway();
        });

        $this->app->bind(GmuAiService::class, function ($app) {
            return new GmuAiService(
                $app->make(LlmInterface::class),
                new GrantMatchingEngine(),
                new ComplianceRiskEngine(),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-donors', fn ($user) =>
            $user->hasAnyRole(['coordinator', 'planning_officer'])
        );

        Gate::define('admin-panel', fn ($user) =>
            $user->hasRole('coordinator')
        );

        Gate::define('review-proposals', fn ($user) =>
            $user->hasAnyRole(['coordinator', 'research_scientist', 'legal_officer', 'finance_officer'])
        );

        Gate::define('manage-awards', fn ($user) =>
            $user->hasAnyRole(['coordinator', 'finance_officer', 'legal_officer'])
        );

        Gate::define('view-analytics', fn ($user) =>
            $user->hasAnyRole(['coordinator', 'planning_officer', 'finance_officer'])
        );
    }
}
