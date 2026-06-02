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
    'receiver_account_id',  // (nullable)
    'external_account_number', // (nullable)
    'amount',
    'currency',
    'description',
    'category',
    'transaction_type', // (internal, external)
    'created_at',
];

// Posiljalac
public function senderAccount(){
    return $this->belongsTo(Account::class, 'sender_account_id');
}

//Primalac 
public function reciverAccount(){
    return $this->belongsTo(Account::class, 'receiver_account_id');
}

}
