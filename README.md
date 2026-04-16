# ClinicWeb - Clinic & Patient Management System (EHR)

A comprehensive Clinic & Patient Management System (EHR) built with Laravel API + Vue 3 + Vuexy.

## Features

- Multi-clinic support (SaaS ready)
- Role-based access control (RBAC)
- Appointment scheduling with overlap detection
- Doctor profiles & sessions management
- Patient management
- Dashboard with analytics
- Encounters (Visits)
- Prescriptions with PDF generation
- Billing/Invoicing
- Notifications system
- Custom fields
- Email notifications
- Google Calendar integration
- Audit logging
- Medical history module
- Reports with CSV/Excel export

## Tech Stack

- **Backend:** Laravel 12 (API)
- **Frontend:** Vue 3 + Vuexy Admin Template
- **Auth:** Laravel Sanctum
- **Database:** SQLite (default) / MySQL / PostgreSQL

## Requirements

- PHP 8.2+
- Node.js 18+
- Composer
- SQLite, MySQL, or PostgreSQL

## Installation

```bash
# Clone the repository
git clone https://github.com/Nassim-sadi/clinic-web.git
cd clinic-web

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed permissions
php artisan db:seed --class=PermissionSeeder

# Build assets
npm run build

# Start the server
php artisan serve
```

## Running with Dev Mode

```bash
# Run all services (backend, queue, logs, frontend)
composer dev
```

Or run individually:
```bash
php artisan serve          # Backend on http://localhost:8000
npm run dev               # Frontend on http://localhost:5173
```

## Tier System

Configure via `.env`:
```env
APP_TIER=one_doctor    # Solo practitioner
APP_TIER=clinic        # Single clinic, multiple doctors
APP_TIER=multi_clinic  # Full SaaS
```

## API Routes

The API is available at `/api`. Key endpoints:

- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register
- `GET /api/appointments` - List appointments
- `GET /api/patients` - List patients
- `GET /api/doctors` - List doctors
- `GET /api/dashboard/stats` - Dashboard statistics

View all routes:
```bash
php artisan route:list --path=api
```

## Test Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@nsclinic.com | password |

## Project Structure

```
clinic-web/
├── app/
│   ├── Http/Controllers/Api/    # API Controllers
│   ├── Models/                   # Eloquent Models
│   ├── Services/                 # Business Logic
│   └── Observers/                # Model Observers
├── resources/js/
│   ├── @core/                    # Vuexy core components
│   ├── @layouts/                 # Vuexy layouts plugin
│   ├── admin/                    # Admin pages
│   └── frontend/                 # Patient portal
├── routes/
│   └── api.php                   # API routes
└── database/
    └── migrations/               # Database migrations
```

## License

MIT
