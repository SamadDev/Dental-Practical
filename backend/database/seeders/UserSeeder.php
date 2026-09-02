<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ────────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@clinic.local'],
            [
                'name'      => 'Admin',
                'password'  => Hash::make('admin123'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
        $admin->assignSyncRole('admin');

        // ── Doctors ─────────────────────────────────────────────────────────────
        $doctors = [];

        $doctor1 = User::firstOrCreate(
            ['email' => 'doctor1@clinic.local'],
            ['name' => 'Dr. Ahmed Kareem', 'password' => Hash::make('doctor123'), 'role' => 'doctor', 'is_active' => true]
        );
        $doctor1->assignSyncRole('doctor');
        $doctors[] = Doctor::firstOrCreate(
            ['user_id' => $doctor1->id],
            ['specialty' => 'General Dentistry', 'phone' => null, 'color' => '#6366f1', 'bio' => 'General dentist with 10 years experience.']
        );

        $doctor2 = User::firstOrCreate(
            ['email' => 'doctor2@clinic.local'],
            ['name' => 'Dr. Sara Hassan', 'password' => Hash::make('doctor123'), 'role' => 'doctor', 'is_active' => true]
        );
        $doctor2->assignSyncRole('doctor');
        $doctors[] = Doctor::firstOrCreate(
            ['user_id' => $doctor2->id],
            ['specialty' => 'Orthodontics', 'phone' => null, 'color' => '#ec4899', 'bio' => 'Orthodontist specialist.']
        );

        // ── Receptionists ─────────────────────────────────────────────────────
        // Each receptionist is assigned to ALL doctors (so they can work at any desk)
        $reception1 = User::firstOrCreate(
            ['email' => 'reception1@clinic.local'],
            ['name' => 'Receptionist 1', 'password' => Hash::make('reception123'), 'role' => 'receptionist', 'is_active' => true]
        );
        $reception1->assignSyncRole('receptionist');
        $reception1->assignedDoctors()->sync(collect($doctors)->pluck('id'));

        $reception2 = User::firstOrCreate(
            ['email' => 'reception2@clinic.local'],
            ['name' => 'Receptionist 2', 'password' => Hash::make('reception123'), 'role' => 'receptionist', 'is_active' => true]
        );
        $reception2->assignSyncRole('receptionist');
        // Assign second receptionist to only doctor1 (simulate morning/evening shift)
        $reception2->assignedDoctors()->sync([$doctors[0]->id]);

        // ── Legacy users (migrate to new format) ───────────────────────────────
        // doctor@clinic.local → becomes Dr. Existing
        $legacyDoctor = User::where('email', 'doctor@clinic.local')->first();
        if ($legacyDoctor) {
            $legacyDoctor->assignSyncRole('doctor');
            Doctor::firstOrCreate(
                ['user_id' => $legacyDoctor->id],
                ['specialty' => 'General Dentistry', 'color' => '#10b981']
            );
        }

        // reception@clinic.local → assign to all doctors
        $legacyReception = User::where('email', 'reception@clinic.local')->first();
        if ($legacyReception) {
            $legacyReception->assignSyncRole('receptionist');
            $legacyReception->assignedDoctors()->sync(collect($doctors)->pluck('id'));
        }
    }
}
