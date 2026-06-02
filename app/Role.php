<?php

namespace App\Enums;

enum Role: string
{
    case Admin   = 'admin';
    case Manager = 'manager';
    case Client  = 'client';

    public function label(): string
    {
        return match($this) {
            Role::Admin   => 'Administrator',
            Role::Manager => 'Menadžer',
            Role::Client  => 'Klijent',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            Role::Admin => [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'accounts.view', 'accounts.create', 'accounts.freeze',
                'transactions.view', 'transactions.reverse',
            ],
            Role::Manager => [ //treba controller za permission
                'users.view.own',
                'users.create.account',
                'accounts.view.own',
            ],
            Role::Client => [
                'accounts.view',
                'transactions.view', 'transactions.create',
            ],
        };
    }

    public function can(string $permission): bool
    {
        return $this === self::Admin || in_array($permission, $this->permissions());
    }
}