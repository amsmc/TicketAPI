<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_name',
        'price',
        'event_date',
        'location',
        'quantity_available',
        'quantity_sold',
        'status',
        'description',
        'photo',
        'session',
    ];

    protected $casts = [
        'event_date' => 'date',
        'price' => 'integer',
        'quantity_available' => 'integer',
        'quantity_sold' => 'integer',
    ];

    /**
     * Get available quantity
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->quantity_available - $this->quantity_sold;
    }

    /**
     * Check if ticket is available
     */
    public function isAvailable()
    {
        return $this->status === 'active'
            && $this->getAvailableQuantityAttribute() > 0
            && $this->event_date >= now();
    }

    /**
     * Scope for active tickets
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now());
    }
}
