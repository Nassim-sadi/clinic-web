export default [
  {
    title: 'Dashboard',
    icon: { icon: 'tabler-smart-home' },
    children: [
      {
        title: 'Analytics',
        to: 'dashboard',
        icon: { icon: 'tabler-chart-pie' },
      },
    ],
  },
  {
    title: 'Patients',
    to: 'patients',
    icon: { icon: 'tabler-users' },
  },
  {
    title: 'Doctors',
    to: 'doctors',
    icon: { icon: 'tabler-stethoscope' },
  },
  {
    title: 'Appointments',
    to: 'appointments',
    icon: { icon: 'tabler-calendar' },
  },
  {
    title: 'Services',
    to: 'services',
    icon: { icon: 'tabler-stethoscope' },
  },
  {
    title: 'Billing',
    icon: { icon: 'tabler-receipt' },
    children: [
      {
        title: 'Bills',
        to: 'bills',
        icon: { icon: 'tabler-file-invoice' },
      },
      {
        title: 'Reports',
        to: 'reports',
        icon: { icon: 'tabler-chart-bar' },
      },
    ],
  },
  {
    title: 'Prescriptions',
    to: 'prescriptions',
    icon: { icon: 'tabler-prescription' },
  },
  {
    title: 'Queue',
    to: 'queue',
    icon: { icon: 'tabler-users-group' },
  },
  {
    title: 'Settings',
    icon: { icon: 'tabler-settings' },
    children: [
      {
        title: 'Account Settings',
        to: 'pages-account-settings',
        icon: { icon: 'tabler-user' },
      },
      {
        title: 'User Management',
        to: 'users',
        icon: { icon: 'tabler-user-cog' },
      },
    ],
  },
]
