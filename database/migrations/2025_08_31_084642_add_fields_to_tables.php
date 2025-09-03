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
       // Add missing fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Add missing fields to tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('quantity_sold')->default(0)->after('quantity_available');
            $table->text('description')->nullable()->after('quantity_sold');
            $table->string('location')->after('description');
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active')->after('location');
        });

        // Add missing fields to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'confirmed', 'rejected'])->default('pending')->after('transaction_date');
            $table->text('qr_code')->nullable()->after('payment_status');
            $table->string('reference_number')->unique()->after('qr_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            //
        });
    }
};
