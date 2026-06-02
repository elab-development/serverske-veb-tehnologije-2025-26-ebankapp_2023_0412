<?php

namespace App\Models;

use App\Models\Transaction;
use App\Enums\AccStatus;
use App\Enums\AccType;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Account extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'account_number',
        'type',
        'currency',
        'balance',
        'status',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'type'      => AccType::class,
            'currency'  => Currency::class,
            'status'    => AccStatus::class,
            'balance'   => 'decimal:2',
            'is_shared' => 'boolean',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_user')
                    ->withTimestamps();
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_account_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'receiver_account_id');
    }



    public function isActive(): bool
    {
        return $this->status === AccStatus::Active;
    }

    public function isForeign(): bool
    {
        return $this->type === AccType::Devizni;
    }

    public function hasSufficientFunds(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}