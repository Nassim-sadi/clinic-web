<?php

return [
    'tier' => env('APP_TIER', 'multi_clinic'),

    'tiers' => [
        'one_doctor' => [
            'name' => 'One Doctor',
            'description' => 'Solo practitioner - single doctor, single location',
            'modules' => ['dashboard', 'appointments', 'patients', 'services', 'booking'],
            'settings' => [
                'max_doctors' => 1,
                'max_clinics' => 1,
                'max_patients' => PHP_INT_MAX,
                'allow_registration' => false,
                'show_billing' => false,
                'show_encounters' => false,
                'show_prescriptions' => false,
                'multi_doctor' => false,
                'clinic_switcher' => false,
            ],
        ],
        'clinic' => [
            'name' => 'Clinic',
            'description' => 'Single clinic with multiple doctors and staff',
            'modules' => ['dashboard', 'appointments', 'patients', 'services', 'doctors', 'users', 'encounters', 'prescriptions', 'billing', 'booking'],
            'settings' => [
                'max_doctors' => PHP_INT_MAX,
                'max_clinics' => 1,
                'max_patients' => PHP_INT_MAX,
                'allow_registration' => true,
                'show_billing' => true,
                'show_encounters' => true,
                'show_prescriptions' => true,
                'multi_doctor' => true,
                'clinic_switcher' => false,
            ],
        ],
        'multi_clinic' => [
            'name' => 'Multi-Clinic',
            'description' => 'Full SaaS - manage multiple clinics',
            'modules' => ['dashboard', 'appointments', 'patients', 'services', 'doctors', 'users', 'clinics', 'encounters', 'prescriptions', 'billing', 'reports', 'booking'],
            'settings' => [
                'max_doctors' => PHP_INT_MAX,
                'max_clinics' => PHP_INT_MAX,
                'max_patients' => PHP_INT_MAX,
                'allow_registration' => true,
                'show_billing' => true,
                'show_encounters' => true,
                'show_prescriptions' => true,
                'multi_doctor' => true,
                'clinic_switcher' => true,
            ],
        ],
    ],
];
