<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_per_ticket', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->enum('session', ['Pagi-Siang', 'Siang-Sore', 'Malam']);
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->dateTime('transaction_date')->nullable();

            // Midtrans specific fields
            $table->string('snap_token')->nullable();
            $table->enum('payment_status', ['pending', 'success', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->string('payment_type')->nullable(); // e.g., 'qris', 'gopay', 'bank_transfer'
            $table->string('transaction_id')->nullable(); // Midtrans transaction ID
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
