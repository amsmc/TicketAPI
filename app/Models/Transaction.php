<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Ticket_code;

class Transaction extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'ticket_id', 'quantity', 'price_per_ticket',
        'total_amount', 'session', 'customer_name', 'customer_email',
        'customer_phone', 'snap_token', 'payment_status', 'payment_type',
        'transaction_id', 'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'price_per_ticket' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ticketCodes()
    {
        return $this->hasMany(Ticket_code::class);
    }
}
