<script setup>
import { dashboard } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

definePage({
  meta: { title: 'Dashboard' },
})

const authStore = useAuthStore()

const stats = ref({
  totalAppointments: 0,
  todayAppointments: 0,
  totalPatients: 0,
  pendingBills: 0,
  totalRevenue: 0,
})

const recentAppointments = ref([])
const appointmentStats = ref({})
const loading = ref(true)

const fetchDashboardData = async () => {
  loading.value = true
  try {
    const [statsRes, recentRes] = await Promise.all([
      dashboard.stats(),
      dashboard.recentAppointments({ limit: 5 }),
    ])

    stats.value = {
      totalAppointments: statsRes.data.total_appointments || 0,
      todayAppointments: statsRes.data.today_appointments || 0,
      totalPatients: statsRes.data.total_patients || 0,
      pendingBills: statsRes.data.pending_bills || 0,
      totalRevenue: statsRes.data.total_revenue || 0,
    }

    recentAppointments.value = recentRes.data || []
  }
  catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})

const formatTime = time => {
  if (!time)
    return ''
  
  return time.substring(0, 5)
}

const getStatusColor = status => {
  const colors = {
    scheduled: 'info',
    confirmed: 'success',
    completed: 'primary',
    cancelled: 'error',
    no_show: 'warning',
  }

  
  return colors[status] || 'grey'
}

const formatCurrency = value => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value || 0)
}
</script>

<template>
  <VRow class="match-height">
    <VCol
      cols="12"
      md="3"
      sm="6"
    >
      <VCard :loading="loading">
        <VCardText class="d-flex align-center">
          <VSkeletonLoader
            v-if="loading"
            type="list-item-avatar-two-line"
          />
          <template v-else>
            <VAvatar
              color="primary"
              variant="tonal"
              :size="48"
              class="me-4"
            >
              <VIcon
                icon="tabler-calendar"
                size="24"
              />
            </VAvatar>
            <div>
              <span class="text-body-2 text-disabled">Today's Appointments</span>
              <h4 class="text-h4">
                {{ stats.todayAppointments }}
              </h4>
            </div>
          </template>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="3"
      sm="6"
    >
      <VCard :loading="loading">
        <VCardText class="d-flex align-center">
          <VSkeletonLoader
            v-if="loading"
            type="list-item-avatar-two-line"
          />
          <template v-else>
            <VAvatar
              color="success"
              variant="tonal"
              :size="48"
              class="me-4"
            >
              <VIcon
                icon="tabler-users"
                size="24"
              />
            </VAvatar>
            <div>
              <span class="text-body-2 text-disabled">Total Patients</span>
              <h4 class="text-h4">
                {{ stats.totalPatients }}
              </h4>
            </div>
          </template>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="3"
      sm="6"
    >
      <VCard :loading="loading">
        <VCardText class="d-flex align-center">
          <VSkeletonLoader
            v-if="loading"
            type="list-item-avatar-two-line"
          />
          <template v-else>
            <VAvatar
              color="warning"
              variant="tonal"
              :size="48"
              class="me-4"
            >
              <VIcon
                icon="tabler-receipt"
                size="24"
              />
            </VAvatar>
            <div>
              <span class="text-body-2 text-disabled">Pending Bills</span>
              <h4 class="text-h4">
                {{ stats.pendingBills }}
              </h4>
            </div>
          </template>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="3"
      sm="6"
    >
      <VCard :loading="loading">
        <VCardText class="d-flex align-center">
          <VSkeletonLoader
            v-if="loading"
            type="list-item-avatar-two-line"
          />
          <template v-else>
            <VAvatar
              color="info"
              variant="tonal"
              :size="48"
              class="me-4"
            >
              <VIcon
                icon="tabler-cash"
                size="24"
              />
            </VAvatar>
            <div>
              <span class="text-body-2 text-disabled">Total Revenue</span>
              <h4 class="text-h4">
                {{ formatCurrency(stats.totalRevenue) }}
              </h4>
            </div>
          </template>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      lg="8"
    >
      <VCard>
        <VCardText class="d-flex align-center justify-space-between">
          <h5>Recent Appointments</h5>
          <VBtn
            variant="text"
            size="small"
            :to="{ name: 'appointments' }"
          >
            View All
          </VBtn>
        </VCardText>

        <VDataTable
          :headers="[
            { title: 'Patient', key: 'patient.name' },
            { title: 'Doctor', key: 'doctor.name' },
            { title: 'Date', key: 'date' },
            { title: 'Time', key: 'time' },
            { title: 'Status', key: 'status' },
          ]"
          :items="recentAppointments"
          :loading="loading"
        >
          <template #item.time="{ item }">
            {{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}
          </template>
          <template #item.status="{ item }">
            <VChip
              :color="getStatusColor(item.status)"
              size="small"
            >
              {{ item.status }}
            </VChip>
          </template>
        </VDataTable>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <VCard :loading="loading">
        <VCardText>
          <h5 class="mb-4">
            Quick Actions
          </h5>
          <div class="d-flex flex-column gap-2">
            <VBtn
              color="primary"
              variant="tonal"
              block
              :to="{ name: 'appointments' }"
            >
              <VIcon
                icon="tabler-plus"
                class="me-2"
              />
              New Appointment
            </VBtn>
            <VBtn
              color="success"
              variant="tonal"
              block
              :to="{ name: 'patients' }"
            >
              <VIcon
                icon="tabler-user-plus"
                class="me-2"
              />
              Add Patient
            </VBtn>
            <VBtn
              color="info"
              variant="tonal"
              block
              :to="{ name: 'queue' }"
            >
              <VIcon
                icon="tabler-users"
                class="me-2"
              />
              Waiting Queue
            </VBtn>
          </div>
        </VCardText>
      </VCard>

      <VCard
        :loading="loading"
        class="mt-4"
      >
        <VCardText>
          <h5 class="mb-4">
            Welcome, {{ authStore.currentUser?.name || 'User' }}
          </h5>
          <p class="text-body-2 text-disabled">
            Role: <strong>{{ authStore.currentUser?.role || 'N/A' }}</strong>
          </p>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
