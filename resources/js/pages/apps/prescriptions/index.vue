<script setup>
import { prescriptions } from '@/services/api'

definePage({
  meta: { title: 'Prescriptions' },
})

const search = ref('')
const prescriptionsList = ref([])
const loading = ref(false)

const headers = [
  { title: 'Patient', key: 'patient.name' },
  { title: 'Doctor', key: 'doctor.name' },
  { title: 'Encounter', key: 'encounter_id' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchPrescriptions = async () => {
  loading.value = true
  try {
    const response = await prescriptions.list({ search: search.value })

    prescriptionsList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch prescriptions:', error)
  }
  finally {
    loading.value = false
  }
}

watch(search, fetchPrescriptions, { debounce: 300 })
onMounted(fetchPrescriptions)
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
            placeholder="Search prescriptions..."
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
          v-for="prescription in prescriptionsList"
          :key="prescription.id"
        >
          <td>{{ prescription.patient?.name || 'N/A' }}</td>
          <td>{{ prescription.doctor?.name || 'N/A' }}</td>
          <td>{{ prescription.encounter_id || 'N/A' }}</td>
          <td>{{ prescription.created_at }}</td>
          <td>
            <VBtn
              icon
              variant="text"
              size="small"
              @click="prescriptions.download(prescription.id)"
            >
              <VIcon icon="tabler-download" />
            </VBtn>
          </td>
        </tr>
        <tr v-if="prescriptionsList.length === 0 && !loading">
          <td
            colspan="5"
            class="text-center text-disabled pa-4"
          >
            No prescriptions found
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>
</template>
