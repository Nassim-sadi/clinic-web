export const redirects = [
  {
    path: '/',
    name: 'index',
    redirect: () => {
      const userData = useCookie('userData').value
      const userRole = userData?.role
      if (userRole === 'super_admin' || userRole === 'clinic_admin' || userRole === 'admin')
        return { name: 'dashboard' }
      if (userRole === 'doctor')
        return { name: 'dashboard' }
      if (userRole === 'patient')
        return { name: 'dashboard' }

      return { name: 'login' }
    },
  },
  {
    path: '/pages/user-profile',
    name: 'pages-user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
  },
  {
    path: '/pages/account-settings',
    name: 'pages-account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
  },
]

export const routes = [
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/pages/dashboards/analytics.vue'),
    meta: { title: 'Dashboard', requiresAuth: true },
  },
  {
    path: '/patients',
    name: 'patients',
    component: () => import('@/pages/apps/patients/index.vue'),
    meta: { title: 'Patients', requiresAuth: true, roles: ['super_admin', 'clinic_admin', 'admin', 'doctor'] },
  },
  {
    path: '/doctors',
    name: 'doctors',
    component: () => import('@/pages/apps/doctors/index.vue'),
    meta: { title: 'Doctors', requiresAuth: true, roles: ['super_admin', 'clinic_admin'] },
  },
  {
    path: '/appointments',
    name: 'appointments',
    component: () => import('@/pages/apps/appointments/index.vue'),
    meta: { title: 'Appointments', requiresAuth: true, roles: ['super_admin', 'clinic_admin', 'admin', 'doctor', 'secretary'] },
  },
  {
    path: '/appointments/:id',
    name: 'appointment-view',
    component: () => import('@/pages/apps/appointments/[id].vue'),
    meta: { title: 'Appointment Details', requiresAuth: true },
  },
  {
    path: '/services',
    name: 'services',
    component: () => import('@/pages/apps/services/index.vue'),
    meta: { title: 'Services', requiresAuth: true, roles: ['super_admin', 'clinic_admin'] },
  },
  {
    path: '/bills',
    name: 'bills',
    component: () => import('@/pages/apps/bills/index.vue'),
    meta: { title: 'Billing', requiresAuth: true, roles: ['super_admin', 'clinic_admin'] },
  },
  {
    path: '/prescriptions',
    name: 'prescriptions',
    component: () => import('@/pages/apps/prescriptions/index.vue'),
    meta: { title: 'Prescriptions', requiresAuth: true, roles: ['super_admin', 'clinic_admin', 'doctor'] },
  },
  {
    path: '/encounters',
    name: 'encounters',
    component: () => import('@/pages/apps/encounters/index.vue'),
    meta: { title: 'Encounters', requiresAuth: true, roles: ['super_admin', 'clinic_admin', 'doctor'] },
  },
  {
    path: '/queue',
    name: 'queue',
    component: () => import('@/pages/apps/queue/index.vue'),
    meta: { title: 'Waiting Queue', requiresAuth: true, roles: ['super_admin', 'clinic_admin', 'doctor', 'secretary'] },
  },
  {
    path: '/reports',
    name: 'reports',
    component: () => import('@/pages/apps/reports/index.vue'),
    meta: { title: 'Reports', requiresAuth: true, roles: ['super_admin', 'clinic_admin'] },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/views/pages/account-settings/AccountSettingsAccount.vue'),
    meta: { title: 'Settings', requiresAuth: true },
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('@/pages/apps/users/index.vue'),
    meta: { title: 'User Management', requiresAuth: true, roles: ['super_admin', 'clinic_admin'] },
  },
]
