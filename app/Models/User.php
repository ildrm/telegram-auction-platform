<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Users\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'display_name',
        'email',
        'password',
        'locale',
        'timezone',
        'status',
        'is_verified',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return HasMany<Auction, $this> */
    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'seller_id');
    }

    /** @return HasMany<Bid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class, 'bidder_id');
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function auctionNotifications(): HasMany
    {
        return $this->hasMany(AuctionNotification::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot(['assigned_by', 'created_at']);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->status === UserStatus::Active
            && (
                $this->hasPermission('audit.view')
                || $this->hasPermission('category.manage')
                || $this->hasPermission('report.manage')
                || $this->hasPermission('user.manage')
            );
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_id' => 'integer',
            'status' => UserStatus::class,
            'is_verified' => 'boolean',
            'last_seen_at' => 'immutable_datetime',
        ];
    }
}
