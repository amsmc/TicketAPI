<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_id',
        'quantity',
        'total_price',
        'transaction_date',
        'payment_status',
        'qr_code',
        'reference_number'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total_price' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function finance()
    {
        return $this->hasOne(Finance::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $transaction->reference_number = 'TRX-' . strtoupper(uniqid());
        });
    }
}

