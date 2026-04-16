# NSclinic - Clinic & Patient Management System (EHR)

## Project Overview

Inspired by [KiviCare](https://kivicare.io) - A comprehensive Clinic & Patient Management System (EHR) with features for appointment scheduling, patient records, billing, prescriptions, and telemedicine.

**Tech Stack:**
- Backend: Laravel 13 (API)
- Frontend: Vue 3 + Vuetify 3
- Calendar: FullCalendar
- Desktop: Wails (Go) ready
- Validation: Vuelidate
- Toast: Vuetify snackbar
- Styling: **Vuexy Admin Template** (fully imported)
- Auth: Laravel Sanctum (Cookie-based for web, Token-based for desktop)

---

## ✅ COMPLETED: Vuexy Integration

**Status: Vuexy template fully integrated**

### Completed Tasks:
1. [x] Copy Vuexy @core (components, composables, stores, utils, SCSS)
2. [x] Copy Vuexy @layouts plugin (VerticalNavLayout, HorizontalNavLayout)
3. [x] Copy Vuexy layouts (default.vue, blank.vue, components)
4. [x] Copy Vuexy navigation (vertical, horizontal)
5. [x] Copy Vuexy dashboard widgets
6. [x] Copy Vuexy styles and images
7. [x] Create themeConfig.js for NSclinic
8. [x] Add all necessary vite aliases (@core, @layouts, @styles, @configured-variables, @images, @themeConfig)
9. [x] Install dependencies: pinia, vue3-apexcharts, vue3-perfect-scrollbar, @iconify/vue, @tabler/icons-vue, @floating-ui/dom, @casl/vue, @casl/ability
10. [x] Create Vuexy-based AdminLayout (VuexyAdminLayout.vue)
11. [x] Create NSclinic navigation (nsclinic.js)
12. [x] Create NavbarNotifications and NavbarUserMenu components
13. [x] Update app.js to initialize layouts plugin and config store
14. [x] Update router to use VuexyAdminLayout
15. [x] Fix stats ref access issues
16. [x] Remove duplicate apexcharts registration
17. [x] Fix useSkins import path

### Vuexy Layout Structure:
```
@layouts/
├── components/
│   ├── VerticalNavLayout.vue   (Main vertical nav wrapper)
│   ├── VerticalNav.vue         (Navigation sidebar)
│   ├── VerticalNavLink.vue     (Nav items)
│   ├── VerticalNavGroup.vue    (Nav groups)
│   └── ...
├── stores/config.js           (Layout config store)
├── config.js                   (Layout configuration)
├── index.js                    (Plugin entry)
└── styles/                     (Layout SCSS)

admin/layouts/
├── VuexyAdminLayout.vue        (NSclinic admin layout)
└── components/
    ├── NavbarNotifications.vue
    └── NavbarUserMenu.vue
```

### Dashboard Widgets (from Vuexy):
- `AnalyticsSalesOverview.vue` - Sales/Appointment overview card
- `AnalyticsEarningReportsWeeklyOverview.vue` - Weekly trends chart
- `AnalyticsSupportTracker.vue` - Radial bar chart with stats
- `AnalyticsTotalEarning.vue` - Total revenue card
- `AnalyticsAverageDailySales.vue` - Sparkline card
- `AnalyticsProjectTable.vue` - Data table

---

## Tier System (SaaS Ready)

NSclinic supports **3 tiers** configurable via `.env`:

```env
APP_TIER=one_doctor    # Solo practitioner
APP_TIER=clinic        # Single clinic, multiple doctors
APP_TIER=multi_clinic  # Full SaaS
```

### Tier Comparison

| Feature | One Doctor | Clinic | Multi-clinic |
|---------|-----------|--------|--------------|
| Single Doctor | ✅ | ✅ | ✅ |
| Multiple Doctors | ❌ | ✅ | ✅ |
| Multiple Staff | ❌ | ✅ | ✅ |
| Encounters | ❌ | ✅ | ✅ |
| Prescriptions | ❌ | ✅ | ✅ |
| Billing | ❌ | ✅ | ✅ |
| Multiple Clinics | ❌ | ❌ | ✅ |
| Clinic Switcher | ❌ | ❌ | ✅ |
| Reports | ❌ | ❌ | ✅ |

---

## KiviCare Feature Comparison

### Implemented ✅
| Feature | Status | KiviCare Equivalent |
|---------|--------|---------------------|
| Multi-clinic support | ✅ | ✅ |
| Role-based access (RBAC) | ✅ | ✅ |
| Appointments with overlap detection | ✅ | ✅ |
| Doctor profiles & sessions | ✅ | ✅ |
| Patient management | ✅ | ✅ |
| Services management | ✅ | ✅ |
| Dashboard with stats | ✅ | ✅ |
| Vuelidate + Toast notifications | ✅ | ✅ |
| SoftDeletes on records | ✅ | ✅ |
| Carbon time handling | ✅ | ✅ |
| Encounters (Visits) | ✅ | ✅ |
| Prescriptions | ✅ | ✅ |
| Billing/Invoicing | ✅ | ✅ |
| Notifications system | ✅ | ✅ |
| Theme Customizer | ✅ | Vuexy Customizer |
| Skeleton loaders | ✅ | ✅ |
| Vuexy UI (imported) | ✅ | Custom |
| Medical History module | ✅ | ✅ |
| **PDF Generation** | ✅ | Invoice & Prescription PDFs with DomPDF |
| **Translations/i18n** | ✅ | English, Arabic, French with vue-i18n |
| **Reports/Export** | ✅ | Summary, appointments, patients, billing reports with CSV export |
| **Custom Fields** | ✅ | Dynamic form fields for patients, encounters, appointments |
| **Email Notifications** | ✅ | Send prescriptions/reports via email |
| **Google Calendar** | ✅ | Sync appointments with Google Calendar |
| **Audit Logging** | ✅ | Track all changes to records |
| **Patient Portal** | ✅ | Enhanced patient dashboard with prescriptions, billing, medical history |
| **Excel Export** | ✅ | Export reports to Excel with formatting |
| **Dashboard Widgets** | ✅ | Customizable dashboard with draggable widgets |
| **Automated Reminders** | ✅ | Scheduled appointment reminders via email |

### To Implement 📋
| Feature | Priority | Notes |
|---------|----------|-------|
| ~~2FA Authentication~~ | ✅ DONE | Email-based two-factor authentication |
| ~~Recurring Appointments~~ | ✅ DONE | For chronic patients / regular visits |
| ~~Waiting Room Queue~~ | ✅ DONE | Queue management for walk-ins |
| ~~Wails Desktop App~~ | ✅ DONE | Desktop application packaging |
| **API Documentation** | LOW | Swagger/OpenAPI documentation |
| ~~Unit Tests~~ | ✅ DONE | PHPUnit tests for controllers & services |
| **Vuexy Integration** | HIGH | Import full Vuexy template |

---

## Authentication (Updated)

### Strategy:
- **Web**: Cookie-based auth with Sanctum (`withCredentials: true`)
- **Desktop**: Token-based auth (`VITE_APP_MODE=desktop`)

### Implementation:
- `resources/js/stores/auth.js` - Pinia auth store
- `resources/js/services/api.js` - Axios with conditional auth

---

## Project Structure

```
NSclinic/
├── app/                      (Laravel Backend - unchanged)
├── nsclinic-desktop/          (Desktop App - unchanged)
├── resources/js/
│   ├── @core/                (Vuexy @core - NEW)
│   │   ├── components/       (Cards, Tables, etc.)
│   │   ├── composable/       (useTheme, etc.)
│   │   ├── stores/           (Config store)
│   │   └── utils/
│   ├── @layouts/             (Vuexy layouts plugin - NEW)
│   │   ├── VerticalNavLayout.vue
│   │   ├── HorizontalNavLayout.vue
│   │   └── index.js
│   ├── layouts/              (Vuexy layouts - NEW)
│   │   ├── default.vue
│   │   ├── blank.vue
│   │   └── components/       (Navbar, Footer, etc.)
│   ├── navigation/           (Vuexy nav - NEW)
│   │   ├── vertical/
│   │   └── horizontal/
│   ├── plugins/              (Merged)
│   ├── admin/
│   │   ├── components/
│   │   ├── layouts/
│   │   └── pages/
│   └── services/
└── routes/
    └── api.php
```

---

## Running the Project

```bash
# Backend
php artisan serve

# Frontend
npm run dev
npm run build

# Database
php artisan migrate
php artisan db:seed
```

### Test Credentials
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@nsclinic.com | password |
| Patient | john@example.com | password |

---

## References
- [Vuexy Admin Template](https://pixinvent.com/demo/vuexy-vuejs-admin-dashboard-template/)
- [KiviCare](https://kivicare.io)
- [FullCalendar](https://fullcalendar.io/)
- [Vuetify 3](https://vuetifyjs.com/)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
