<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Clinic management
            'view_clinics',
            'create_clinics',
            'edit_clinics',
            'delete_clinics',

            // Doctor management
            'view_doctors',
            'create_doctors',
            'edit_doctors',
            'delete_doctors',

            // Patient management
            'view_patients',
            'create_patients',
            'edit_patients',
            'delete_patients',

            // Appointment management
            'view_appointments',
            'create_appointments',
            'edit_appointments',
            'cancel_appointments',
            'delete_appointments',

            // Service management
            'view_services',
            'create_services',
            'edit_services',
            'delete_services',

            // Encounter/Prescription
            'view_encounters',
            'create_encounters',
            'edit_encounters',
            'delete_encounters',

            // Reports
            'view_reports',
            'export_reports',

            // Settings
            'manage_settings',

            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $clinicAdmin = Role::firstOrCreate(['name' => 'clinic_admin']);
        $clinicAdmin->givePermissionTo([
            'view_clinics', 'edit_clinics',
            'view_doctors', 'create_doctors', 'edit_doctors',
            'view_patients', 'create_patients', 'edit_patients',
            'view_appointments', 'create_appointments', 'edit_appointments', 'cancel_appointments',
            'view_services', 'create_services', 'edit_services',
            'view_encounters', 'create_encounters', 'edit_encounters',
            'view_reports', 'export_reports',
            'manage_settings',
            'view_users', 'create_users', 'edit_users',
        ]);

        $doctor = Role::firstOrCreate(['name' => 'doctor']);
        $doctor->givePermissionTo([
            'view_patients',
            'view_appointments', 'edit_appointments', 'cancel_appointments',
            'view_encounters', 'create_encounters', 'edit_encounters',
        ]);

        $secretary = Role::firstOrCreate(['name' => 'secretary']);
        $secretary->givePermissionTo([
            'view_doctors',
            'view_patients', 'create_patients', 'edit_patients',
            'view_appointments', 'create_appointments', 'edit_appointments', 'cancel_appointments',
        ]);

        $patient = Role::firstOrCreate(['name' => 'patient']);
        $patient->givePermissionTo([
            'view_appointments', 'create_appointments', 'cancel_appointments',
        ]);
    }
}
