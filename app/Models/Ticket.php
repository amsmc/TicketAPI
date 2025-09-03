<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_name',
        'price',
        'event_date',
        'quantity_available',
        'quantity_sold',
        'description',
        'location',
        'status'
    ];

    protected $casts = [
        'event_date' => 'date',
        'price' => 'decimal:2'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getAvailableTicketsAttribute()
    {
        return $this->quantity_available - $this->quantity_sold;
    }
}
