<script setup>
import { appointments } from '@/services/api'

definePage({
  meta: { title: 'Appointment Details' },
})

const route = useRoute()
const appointment = ref(null)
const loading = ref(true)

const fetchAppointment = async () => {
  loading.value = true
  try {
    const res = await appointments.show(route.params.id)

    appointment.value = res.data
  }
  catch (error) {
    console.error('Failed to fetch appointment:', error)
  }
  finally {
    loading.value = false
  }
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

const formatTime = time => time?.substring(0, 5) || ''

onMounted(fetchAppointment)
</script>

<template>
  <VCard v-if="!loading && appointment">
    <VCardTitle class="d-flex align-center justify-space-between">
      <span>Appointment #{{ appointment.id }}</span>
      <VChip :color="getStatusColor(appointment.status)">
        {{ appointment.status }}
      </VChip>
    </VCardTitle>

    <VDivider />

    <VCardText>
      <VRow>
        <VCol
          cols="12"
          md="6"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Patient
          </h6>
          <p class="text-body-1">
            {{ appointment.patient?.name || 'N/A' }}
          </p>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Doctor
          </h6>
          <p class="text-body-1">
            {{ appointment.doctor?.name || 'N/A' }}
          </p>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Service
          </h6>
          <p class="text-body-1">
            {{ appointment.service?.name || 'N/A' }}
          </p>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Date
          </h6>
          <p class="text-body-1">
            {{ appointment.date }}
          </p>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Time
          </h6>
          <p class="text-body-1">
            {{ formatTime(appointment.start_time) }} - {{ formatTime(appointment.end_time) }}
          </p>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Type
          </h6>
          <p class="text-body-1">
            {{ appointment.type || 'N/A' }}
          </p>
        </VCol>

        <VCol
          v-if="appointment.notes"
          cols="12"
        >
          <h6 class="text-body-2 text-disabled mb-1">
            Notes
          </h6>
          <p class="text-body-1">
            {{ appointment.notes }}
          </p>
        </VCol>
      </VRow>
    </VCardText>

    <VDivider />

    <VCardText>
      <VBtn
        variant="outlined"
        :to="{ name: 'appointments' }"
      >
        Back
      </VBtn>
    </VCardText>
  </VCard>

  <VCard v-else-if="loading">
    <VCardText class="text-center pa-8">
      <VProgressCircular indeterminate />
    </VCardText>
  </VCard>
</template>
