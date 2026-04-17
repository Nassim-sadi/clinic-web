<script setup>
import { encounters } from '@/services/api'

definePage({
  meta: { title: 'Encounters' },
})

const search = ref('')
const encountersList = ref([])
const loading = ref(false)

const headers = [
  { title: 'Patient', key: 'patient.name' },
  { title: 'Doctor', key: 'doctor.name' },
  { title: 'Appointment', key: 'appointment_id' },
  { title: 'Chief Complaint', key: 'chief_complaint' },
  { title: 'Status', key: 'status' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchEncounters = async () => {
  loading.value = true
  try {
    const response = await encounters.list({ search: search.value })

    encountersList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch encounters:', error)
  }
  finally {
    loading.value = false
  }
}

const getStatusColor = status => {
  const colors = {
    open: 'info',
    in_progress: 'warning',
    completed: 'success',
    cancelled: 'error',
  }

  
  return colors[status] || 'grey'
}

watch(search, fetchEncounters, { debounce: 300 })
onMounted(fetchEncounters)
</script>

<template>
  <VCard>
    <VCardText>
      <VRow>
        <VCol
          cols="12"
          md="6"
        >
          <AppTextField
            v-model="search"
            placeholder="Search encounters..."
            prepend-inner-icon="tabler-search"
            density="compact"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VTable>
      <thead>
        <tr>
          <th
            v-for="header in headers"
            :key="header.key"
          >
            {{ header.title }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="encounter in encountersList"
          :key="encounter.id"
        >
          <td>{{ encounter.patient?.name || 'N/A' }}</td>
          <td>{{ encounter.doctor?.name || 'N/A' }}</td>
          <td>{{ encounter.appointment_id || 'N/A' }}</td>
          <td>{{ encounter.chief_complaint || 'N/A' }}</td>
          <td>
            <VChip
              :color="getStatusColor(encounter.status)"
              size="small"
            >
              {{ encounter.status }}
            </VChip>
          </td>
          <td>{{ encounter.created_at }}</td>
          <td>
            <VBtn
              icon
              variant="text"
              size="small"
            >
              <VIcon icon="tabler-eye" />
            </VBtn>
          </td>
        </tr>
        <tr v-if="encountersList.length === 0 && !loading">
          <td
            colspan="7"
            class="text-center text-disabled pa-4"
          >
            No encounters found
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>
</template>
