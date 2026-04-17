# ClinicWeb - Clinic & Patient Management System (EHR)

## Project Overview

Inspired by [KiviCare](https://kivicare.io) - A comprehensive Clinic & Patient Management System (EHR) with features for appointment scheduling, patient records, billing, prescriptions, and telemedicine.

**Tech Stack:**
- Backend: Laravel 12 (API)
- Frontend: Vue 3 + Vuexy Admin Template
- Auth: Laravel Sanctum
- PDF: DomPDF
- Permissions: Spatie Permission

---

## Current Status

### ✅ Backend - COMPLETE

**Models (18):**
`Appointment`, `AuditLog`, `Bill`, `BillItem`, `Clinic`, `CustomField`, `DoctorProfile`, `DoctorSession`, `Encounter`, `EntityCustomFieldValue`, `MedicalHistory`, `Notification`, `Patient`, `Prescription`, `RecurringAppointment`, `Service`, `User`, `WaitingQueue`

**Controllers (22):**
`Auth`, `Appointment`, `Bill`, `Clinic`, `CustomField`, `Dashboard`, `Doctor`, `Email`, `Encounter`, `GoogleCalendar`, `MedicalHistory`, `Notification`, `Patient`, `Prescription`, `RecurringAppointment`, `Reports`, `Service`, `Tier`, `TwoFactorAuth`, `User`, `WaitingQueue`, `AuditLog`

**Services (7):**
`AppointmentService`, `EmailService`, `ExportService`, `GoogleCalendarService`, `NotificationService`, `PdfService`, `TwoFactorAuthService`

**Observers (5):**
`AppointmentObserver`, `BillObserver`, `EncounterObserver`, `PatientObserver`, `UserObserver`

**Migrations:** 29 tables created
**API Routes:** 129 endpoints registered

### ✅ Frontend - IN PROGRESS

**Completed:**
- [x] Theme config updated for ClinicWeb
- [x] API service (`services/api.js`) - Full CRUD for all entities
- [x] Auth store (`stores/auth.js`) - Login, register, logout
- [x] Composables: `useToast`, `useNotifications`
- [x] Router guards for authentication & role-based access
- [x] Login page connected to API
- [x] Dashboard page connected to API
- [x] Patients page (list view)
- [x] Doctors page (list view)
- [x] Appointments page (list view)
- [x] Services page (list view)
- [x] Billing page (list view)
- [x] Prescriptions page (list view)
- [x] Encounters page (list view)
- [x] Waiting Queue page with actions
- [x] Reports page

**To Do:**
- [ ] Patient create/edit forms
- [ ] Doctor create/edit forms
- [ ] Appointment create/edit forms
- [ ] Better navigation sidebar
- [ ] Patient portal pages
- [ ] Notifications panel in navbar
- [ ] User profile page
- [ ] Settings page
- [ ] PDF views for prescriptions/bills

---

## Features

### Implemented ✅
| Feature | Backend | Frontend |
|---------|---------|----------|
| Multi-clinic support | ✅ | 🔄 |
| Role-based access (RBAC) | ✅ | ✅ |
| Appointments | ✅ | ✅ |
| Doctor profiles & sessions | ✅ | ✅ |
| Patient management | ✅ | ✅ |
| Services management | ✅ | ✅ |
| Dashboard with stats | ✅ | ✅ |
| Encounters (Visits) | ✅ | ✅ |
| Prescriptions | ✅ | ✅ |
| Billing/Invoicing | ✅ | ✅ |
| Notifications system | ✅ | 🔄 |
| Custom fields | ✅ | ❌ |
| Medical History | ✅ | ❌ |
| PDF Generation | ✅ | 🔄 |
| Reports/Export | ✅ | ✅ |
| Email Notifications | ✅ | ❌ |
| Google Calendar | ✅ | ❌ |
| Audit Logging | ✅ | ❌ |
| Waiting Queue | ✅ | ✅ |
| 2FA Authentication | ✅ | ❌ |
| Recurring Appointments | ✅ | ❌ |

---

## API Endpoints

Key endpoints:
- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register
- `GET /api/dashboard/stats` - Dashboard statistics
- `GET/POST /api/appointments` - Appointments CRUD
- `GET/POST /api/patients` - Patients CRUD
- `GET/POST /api/doctors` - Doctors CRUD
- `GET/POST /api/services` - Services CRUD
- `GET/POST /api/bills` - Billing CRUD
- `GET/POST /api/prescriptions` - Prescriptions CRUD
- `GET/POST /api/encounters` - Encounters CRUD

View all routes:
```bash
php artisan route:list --path=api
```

---

## Project Structure

```
clinic-web/
├── app/
│   ├── Http/Controllers/Api/    # API Controllers
│   ├── Models/                  # Eloquent Models (18)
│   ├── Services/                # Business Logic (7)
│   ├── Observers/              # Model Observers (5)
│   └── Providers/               # Service Providers
├── resources/js/
│   ├── @core/                   # Vuexy core components
│   ├── @layouts/                # Vuexy layouts plugin
│   ├── composables/              # Vue composables (useToast, useNotifications)
│   ├── services/                 # API services (api.js)
│   ├── stores/                  # Pinia stores (auth.js)
│   ├── pages/
│   │   ├── apps/                # Clinic app pages
│   │   │   ├── patients/        # Patients module
│   │   │   ├── doctors/         # Doctors module
│   │   │   ├── appointments/    # Appointments module
│   │   │   ├── services/         # Services module
│   │   │   ├── bills/            # Billing module
│   │   │   ├── prescriptions/   # Prescriptions module
│   │   │   ├── encounters/       # Encounters module
│   │   │   ├── queue/            # Waiting queue module
│   │   │   └── reports/          # Reports module
│   │   └── dashboards/          # Dashboard pages
│   └── plugins/                  # Vue plugins & router
├── routes/
│   └── api.php                   # API routes (129 endpoints)
└── database/
    ├── migrations/               # 29 migrations
    └── seeders/                 # Database seeders
```

---

## Running the Project

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate
php artisan db:seed --class=PermissionSeeder

# Start development
php artisan serve          # API on http://localhost:8000
npm run dev               # Frontend on http://localhost:5173

# Production build
npm run build
```

### Test Credentials
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@nsclinic.com | password |

---

## References
- [Vuexy Admin Template](https://pixinvent.com/demo/vuexy-vuejs-admin-dashboard-template/)
- [KiviCare](https://kivicare.io)
- [Laravel 12](https://laravel.com/docs/12.x)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
