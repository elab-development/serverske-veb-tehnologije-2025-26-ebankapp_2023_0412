<?php

namespace App;

enum Currency: string
{
    case RSD = 'RSD';
    case EUR = 'EUR';
    case USD = 'USD';
    

    public function label(): string
    {
        return match($this) {
            Currency::RSD => 'Srpski dinar',
            Currency::EUR => 'Euro',
            Currency::USD => 'Američki dolar',
            
        };
    }

    public function symbol(): string
    {
        return match($this) {
            Currency::RSD => 'din',
            Currency::EUR => '€',
            Currency::USD => '$',
           
        };
    }
}