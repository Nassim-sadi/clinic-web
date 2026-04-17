<script setup>
const shortcuts = [
  {
    icon: 'tabler-calendar',
    title: 'Calendar',
    subtitle: 'Appointments',
    to: { name: 'appointments' },
  },
  {
    icon: 'tabler-users',
    title: 'Patients',
    subtitle: 'Manage Patients',
    to: { name: 'patients' },
  },
  {
    icon: 'tabler-stethoscope',
    title: 'Doctors',
    subtitle: 'Manage Doctors',
    to: { name: 'doctors' },
  },
  {
    icon: 'tabler-receipt',
    title: 'Billing',
    subtitle: 'Manage Bills',
    to: { name: 'bills' },
  },
  {
    icon: 'tabler-report',
    title: 'Reports',
    subtitle: 'View Reports',
    to: { name: 'reports' },
  },
  {
    icon: 'tabler-settings',
    title: 'Settings',
    subtitle: 'Account Settings',
    to: {
      name: 'pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },
  {
    icon: 'tabler-user-cog',
    title: 'User Management',
    subtitle: 'Manage Users',
    to: { name: 'users' },
  },
  {
    icon: 'tabler-smart-home',
    title: 'Dashboard',
    subtitle: 'Dashboard Analytics',
    to: { name: 'dashboard' },
  },
]
</script>

<template>
  <Shortcuts :shortcuts="shortcuts" />
</template>
