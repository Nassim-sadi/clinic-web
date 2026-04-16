<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);

        $clinic = Clinic::create([
            'name' => 'NSclinic Main',
            'slug' => 'nsclinic-main',
            'description' => 'Primary clinic location',
            'address' => '123 Medical Center Drive',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'USA',
            'phone' => '+1-555-0100',
            'email' => 'contact@nsclinic.com',
            'currency' => 'USD',
            'timezone' => 'America/New_York',
            'is_active' => true,
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@nsclinic.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        $clinicAdmin = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Clinic Manager',
            'first_name' => 'Clinic',
            'last_name' => 'Manager',
            'email' => 'manager@nsclinic.com',
            'password' => Hash::make('password'),
            'role' => 'clinic_admin',
            'is_active' => true,
        ]);
        $clinicAdmin->assignRole('clinic_admin');

        $doctor1 = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Sarah Wilson',
            'first_name' => 'Sarah',
            'last_name' => 'Wilson',
            'email' => 'sarah@nsclinic.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'phone' => '+1-555-0101',
            'is_active' => true,
        ]);
        $doctor1->assignRole('doctor');

        $doctor1->doctorProfile()->create([
            'clinic_id' => $clinic->id,
            'title' => 'Dr.',
            'specialization' => 'General Medicine',
            'qualifications' => 'MD, PhD',
            'experience' => '15 years',
            'bio' => 'Experienced general physician',
            'consultation_fee' => 150.00,
            'slot_duration' => 30,
            'is_available' => true,
        ]);

        $doctor2 = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Michael Brown',
            'first_name' => 'Michael',
            'last_name' => 'Brown',
            'email' => 'michael@nsclinic.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'phone' => '+1-555-0102',
            'is_active' => true,
        ]);
        $doctor2->assignRole('doctor');

        $doctor2->doctorProfile()->create([
            'clinic_id' => $clinic->id,
            'title' => 'Dr.',
            'specialization' => 'Cardiology',
            'qualifications' => 'MD, FACC',
            'experience' => '20 years',
            'bio' => 'Board certified cardiologist',
            'consultation_fee' => 250.00,
            'slot_duration' => 45,
            'is_available' => true,
        ]);

        $clinic->services()->createMany([
            ['name' => 'General Consultation', 'price' => 100.00, 'duration' => 30],
            ['name' => 'Follow-up Visit', 'price' => 50.00, 'duration' => 15],
            ['name' => 'Cardiac Evaluation', 'price' => 300.00, 'duration' => 60],
            ['name' => 'Health Checkup', 'price' => 200.00, 'duration' => 45],
            ['name' => 'Vaccination', 'price' => 75.00, 'duration' => 15],
        ]);

        foreach ([['John Smith', 'john@example.com'], ['Emma Johnson', 'emma@example.com'], ['Robert Davis', 'robert@example.com'], ['Lisa Anderson', 'lisa@example.com'], ['David Martinez', 'david@example.com']] as $i => $p) {
            $user = User::create([
                'clinic_id' => $clinic->id,
                'name' => $p[0],
                'first_name' => explode(' ', $p[0])[0],
                'last_name' => implode(' ', array_slice(explode(' ', $p[0]), 1)),
                'email' => $p[1],
                'password' => Hash::make('password'),
                'role' => 'patient',
                'phone' => '+1-555-'.(1001 + $i),
                'is_active' => true,
            ]);
            $user->assignRole('patient');
            $user->patient()->create([
                'clinic_id' => $clinic->id,
                'gender' => fake()->randomElement(['male', 'female']),
                'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
                'blood_group' => fake()->randomElement(['A+', 'B+', 'O+', 'AB+']),
            ]);
        }

        echo "✅ Seeded: 1 clinic, 4 users, 5 services, 5 patients\n";
        echo "   Login: admin@nsclinic.com / password\n";
    }
}
