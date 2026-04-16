<?php

namespace App\Support;

class TierConfig
{
    public const TIER_ONE_DOCTOR = 'one_doctor';

    public const TIER_CLINIC = 'clinic';

    public const TIER_MULTI_CLINIC = 'multi_clinic';

    public const TIERS = [
        self::TIER_ONE_DOCTOR,
        self::TIER_CLINIC,
        self::TIER_MULTI_CLINIC,
    ];

    public static function getTier(): string
    {
        return config('app.tier', self::TIER_MULTI_CLINIC);
    }

    public static function isOneDoctor(): bool
    {
        return self::getTier() === self::TIER_ONE_DOCTOR;
    }

    public static function isClinic(): bool
    {
        return self::getTier() === self::TIER_CLINIC;
    }

    public static function isMultiClinic(): bool
    {
        return self::getTier() === self::TIER_MULTI_CLINIC;
    }

    public static function isAtLeast(string $tier): bool
    {
        $levels = [
            self::TIER_ONE_DOCTOR => 1,
            self::TIER_CLINIC => 2,
            self::TIER_MULTI_CLINIC => 3,
        ];

        return ($levels[self::getTier()] ?? 0) >= ($levels[$tier] ?? 0);
    }

    public static function getFeatures(): array
    {
        return [
            self::TIER_ONE_DOCTOR => [
                'name' => 'One Doctor',
                'description' => 'Solo practitioner - single doctor, single location',
                'modules' => [
                    'dashboard',
                    'appointments',
                    'patients',
                    'services',
                    'booking',
                ],
                'settings' => [
                    'max_doctors' => 1,
                    'max_clinics' => 1,
                    'max_patients' => PHP_INT_MAX,
                    'allow_registration' => false,
                    'show_billing' => false,
                    'show_encounters' => false,
                    'show_prescriptions' => false,
                    'multi_doctor' => false,
                ],
            ],
            self::TIER_CLINIC => [
                'name' => 'Clinic',
                'description' => 'Single clinic with multiple doctors and staff',
                'modules' => [
                    'dashboard',
                    'appointments',
                    'patients',
                    'services',
                    'doctors',
                    'users',
                    'encounters',
                    'prescriptions',
                    'medical_history',
                    'billing',
                    'booking',
                ],
                'settings' => [
                    'max_doctors' => PHP_INT_MAX,
                    'max_clinics' => 1,
                    'max_patients' => PHP_INT_MAX,
                    'allow_registration' => true,
                    'show_billing' => true,
                    'show_encounters' => true,
                    'show_prescriptions' => true,
                    'multi_doctor' => true,
                ],
            ],
            self::TIER_MULTI_CLINIC => [
                'name' => 'Multi-Clinic',
                'description' => 'Full SaaS - manage multiple clinics',
                'modules' => [
                    'dashboard',
                    'appointments',
                    'patients',
                    'services',
                    'doctors',
                    'users',
                    'clinics',
                    'encounters',
                    'prescriptions',
                    'medical_history',
                    'billing',
                    'booking',
                    'reports',
                ],
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
        ];
    }

    public static function getCurrentFeatures(): array
    {
        return self::getFeatures()[self::getTier()] ?? self::getFeatures()[self::TIER_MULTI_CLINIC];
    }

    public static function hasFeature(string $feature): bool
    {
        $features = self::getCurrentFeatures();

        return in_array($feature, $features['modules']);
    }

    public static function setting(string $key, mixed $default = null): mixed
    {
        $features = self::getCurrentFeatures();

        return $features['settings'][$key] ?? $default;
    }
}
