<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\Role;

class User extends Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'jmbg',
        'address',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => Role::class,
            'is_active'         => 'boolean',
        ];
    }


    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }


    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }


    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isBlocked(): bool
    {
        return $this->is_active === false;
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByRole($query, Role $role)
    {
        return $query->where('role', $role->value);
    }
}