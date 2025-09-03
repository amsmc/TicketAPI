<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Ticket;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(3)->create([

        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'is_active' => true
        ]);

        // owenr
        User::create([
            'name' => 'Event Owner',
            'email' => 'owner@tgmail.com',
            'password' => Hash::make('123456'),
            'role' => 'owner',
            'is_active' => true
        ]);

        User::create([
            'name' => 'John Doe',
            'email' => 'user@gmail.com',
            'password' => Hash::make('123456'),
            'role' => 'user',
            'is_active' => true
        ]);

        Ticket::create([
            'ticket_name' => 'bubarkan dpri 2025',
            'price' => 75.00,
            'event_date' => '2025-07-15',
            'quantity_available' => 500,
            'quantity_sold' => 0,
            'description' => 'kenaikan tunjangan anggota dpri',
            'location' => 'Central Park, Jakarta',
            'status' => 'active'
        ]);

    }
}
