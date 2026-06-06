<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

protected $fillable = [
    'id',
    'sender_account_id',
    'receiver_account_id', 
    'external_account_number',
    'amount',
    'currency',
    'description',
    'category',
    'transaction_type', 
    'created_at',
];

public function senderAccount(){
    return $this->belongsTo(Account::class, 'sender_account_id');
}

public function receiverAccount(){
    return $this->belongsTo(Account::class, 'receiver_account_id');
}

}
