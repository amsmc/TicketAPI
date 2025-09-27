<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Ticket;
use carbon\Carbon;

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
                'ticket_name' => 'Drama Musikal "Cinta di Ujung Senja"',
                'price' => 45000,
                'event_date' => Carbon::now()->addDays(30),
                'quantity_available' => 100,
                'location' => 'iblis baheula',
                'created_at' => now(),
                'updated_at' => now(),
        ]);


    }
}
