<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'f_name',
        'm_name',
        'l_name',
        'std_number',
        'role',
        'is_banned',
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'is_profile_complete',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'is_profile_complete' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function materialAccessEvents()
    {
        return $this->hasMany(MaterialAccessEvents::class, 'user_id');
    }

    public array $excludedFromChangeLogs = [
        'password',
        'remember_token',
        'google_id',
        'is_profile_complete',
    ];

    public function canAccessPanel(Panel $panel): bool
    {

        if (! is_null($this->deleted_at)) {
            return false; // Deny access if user is soft-deleted
        }

        // TODO: Consult with the team if we want to implement this
        // if ($this->is_banned) {
        //     return false; // Deny access if user is banned
        // }

        return match ($panel->getId()) {
            'admin' => in_array($this->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT, UserRole::RR]),
            'user' => in_array($this->role, [UserRole::FACULTY, UserRole::STUDENT]),
            default => false,
        };

        // return true;
    }
}
