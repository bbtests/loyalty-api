<?php

namespace App\Providers;

use App\Events\PurchaseProcessed;
use App\Listeners\ProcessLoyaltyRewards;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PurchaseProcessed::class => [
            ProcessLoyaltyRewards::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Configure ProcessLoyaltyRewards listener to use dynamic queue connection
        $this->listen[PurchaseProcessed::class] = [
            ProcessLoyaltyRewards::class.':'.config('queue.default', 'redis'),
        ];
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
