<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Translation;
use App\Models\User;
use App\Policies\AuctionPolicy;
use App\Policies\BidPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        Gate::policy(Auction::class, AuctionPolicy::class);
        Gate::policy(Bid::class, BidPolicy::class);
        Gate::before(
            fn (User $user): ?bool => $user->hasRole('super-administrator') ? true : null,
        );

        RateLimiter::for('telegram-webhook', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->ip() ?? 'unknown'));

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        if (! app()->runningInConsole() && Schema::hasTable('translations')) {
            $translations = Cache::remember(
                'database_translations',
                now()->addMinutes(10),
                fn () => Translation::query()->get(['locale', 'group', 'key', 'value']),
            );

            foreach ($translations->groupBy('locale') as $locale => $lines) {
                Lang::addLines(
                    $lines->mapWithKeys(
                        fn (Translation $translation): array => [
                            "{$translation->group}.{$translation->key}" => $translation->value,
                        ],
                    )->all(),
                    (string) $locale,
                );
            }
        }
    }
}
