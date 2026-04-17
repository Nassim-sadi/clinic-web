<script setup>
import { appointments } from '@/services/api'
import AppointmentModal from './AppointmentModal.vue'

definePage({
  meta: { title: 'Appointments' },
})

const search = ref('')
const statusFilter = ref('')
const appointmentsList = ref([])
const loading = ref(false)

const pagination = ref({
  page: 1,
  itemsPerPage: 10,
  total: 0,
})

const showModal = ref(false)
const selectedAppointment = ref(null)

const headers = [
  { title: 'Patient', key: 'patient.name' },
  { title: 'Doctor', key: 'doctor.name' },
  { title: 'Service', key: 'service.name' },
  { title: 'Date', key: 'date' },
  { title: 'Time', key: 'time' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchAppointments = async () => {
  loading.value = true
  try {
    const response = await appointments.list({
      page: pagination.value.page,
      per_page: pagination.value.itemsPerPage,
      search: search.value,
      status: statusFilter.value,
    })

    appointmentsList.value = response.data.data || response.data
    pagination.value.total = response.data.total || appointmentsList.value.length
  }
  catch (error) {
    console.error('Failed to fetch appointments:', error)
  }
  finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedAppointment.value = null
  showModal.value = true
}

const openEditModal = appointment => {
  selectedAppointment.value = appointment
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedAppointment.value = null
}

const onSaved = () => {
  fetchAppointments()
}

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

watch([() => pagination.value.page, () => pagination.value.itemsPerPage], fetchAppointments)
watch([search, statusFilter], () => {
  pagination.value.page = 1
  fetchAppointments()
}, { debounce: 300 })

onMounted(fetchAppointments)
</script>

<template>
  <VCard>
    <VCardText>
      <VRow>
        <VCol
          cols="12"
          md="4"
        >
          <AppTextField
            v-model="search"
            placeholder="Search..."
            prepend-inner-icon="tabler-search"
            density="compact"
          />
        </VCol>
        <VCol
          cols="12"
          md="3"
        >
          <VSelect
            v-model="statusFilter"
            :items="[
              { title: 'All', value: '' },
              { title: 'Scheduled', value: 'scheduled' },
              { title: 'Confirmed', value: 'confirmed' },
              { title: 'Completed', value: 'completed' },
              { title: 'Cancelled', value: 'cancelled' },
              { title: 'No Show', value: 'no_show' },
            ]"
            label="Status"
            density="compact"
            clearable
          />
        </VCol>
        <VCol
          cols="12"
          md="5"
          class="text-right"
        >
          <VBtn
            color="primary"
            @click="openCreateModal"
          >
            <VIcon
              icon="tabler-plus"
              class="me-2"
            />
            New Appointment
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>

    <VDataTable
      :headers="headers"
      :items="appointmentsList"
      :loading="loading"
      :items-length="pagination.total"
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
      <template #item.actions="{ item }">
        <VBtn
          icon
          variant="text"
          size="small"
          @click="openEditModal(item)"
        >
          <VIcon icon="tabler-edit" />
        </VBtn>
      </template>
    </VDataTable>

    <VDivider />

    <VCardText class="d-flex align-center justify-center">
      <VPagination
        v-model="pagination.page"
        :length="Math.ceil(pagination.total / pagination.itemsPerPage)"
        :total-visible="7"
        density="compact"
      />
    </VCardText>
  </VCard>

  <Teleport to="body">
    <AppointmentModal
      v-if="showModal"
      :appointment="selectedAppointment"
      @saved="onSaved"
      @closed="closeModal"
    />
  </Teleport>
</template>
