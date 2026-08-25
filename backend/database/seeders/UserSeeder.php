<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@clinic.local',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
            'is_active'=> true,
        ]);

        User::create([
            'name'     => 'Dr. Ahmed',
            'email'    => 'doctor@clinic.local',
            'password' => Hash::make('doctor123'),
            'role'     => 'doctor',
            'is_active'=> true,
        ]);

        User::create([
            'name'     => 'Receptionist',
            'email'    => 'reception@clinic.local',
            'password' => Hash::make('reception123'),
            'role'     => 'receptionist',
            'is_active'=> true,
        ]);
    }
}