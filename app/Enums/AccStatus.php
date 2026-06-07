<?php
 
namespace App\Enums;
 
enum AccStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Closed = 'closed';
 
    public function label(): string
    {
        return match($this) {
            AccStatus::Active => 'Aktivan',
            AccStatus::Frozen => 'Zamrznut',
            AccStatus::Closed => 'Zatvoren',
        };
    }
}