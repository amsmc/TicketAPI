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
        Schema::create('ticket_codes', function (Blueprint $table) {
             $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->string('ticket_code')->unique(); // Kode unik untuk scan
            $table->string('qr_code_path')->nullable(); // Path file QR code
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index('ticket_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_codes');
    }
};
