<?php
 
namespace App\Enums;
 
enum AccType: string
{
    case Dinarski = 'dinarski';
    case Devizni  = 'devizni';
 
    public function label(): string
    {
        return match($this) {
            AccType::Dinarski => 'Dinarski račun',
            AccType::Devizni  => 'Devizni račun',
        };
    }
}
