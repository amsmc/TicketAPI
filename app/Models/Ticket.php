<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_name',
        'price',
        'location',
        'quantity_sold',
        'event_date',
        'quantity_available',
        'description',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessor untuk format mata uang
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    // Accessor untuk status ketersediaan
    public function getAvailabilityStatusAttribute(): string
    {
        if ($this->quantity_available <= 0) {
            return 'Sold Out';
        } elseif ($this->quantity_available <= 10) {
            return 'Terbatas';
        } else {
            return 'Tersedia';
        }
    }

    // Scope untuk filter tiket yang masih tersedia
    public function scopeAvailable($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    // Scope untuk event yang akan datang
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', Carbon::today());
    }
}
