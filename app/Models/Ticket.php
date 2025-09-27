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
        'event_date',
        'description',
        'status',
        'quantity_sold',
        'quantity_available',
        'location',
        'photo',
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

    // Accessor untuk stock (untuk kompatibilitas dengan frontend)
    public function getStockAttribute(): int
    {
        return $this->quantity_available;
    }

    // Accessor untuk title (untuk kompatibilitas dengan frontend)
    public function getTitleAttribute(): string
    {
        return $this->ticket_name;
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

    // Method untuk mengurangi stock saat pembelian
    public function reduceStock($quantity)
    {
        if ($this->quantity_available >= $quantity) {
            $this->quantity_available -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }
}
