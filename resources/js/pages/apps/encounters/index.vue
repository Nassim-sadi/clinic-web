<script setup>
import { encounters } from '@/services/api'
import EncounterModal from './EncounterModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'

definePage({
  meta: { title: 'Encounters' },
})

const search = ref('')
const encountersList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedEncounter = ref(null)

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Patient', key: 'patient.name' },
  { title: 'Doctor', key: 'doctor.name' },
  { title: 'Chief Complaint', key: 'chief_complaint' },
  { title: 'Status', key: 'status' },
  { title: 'Date', key: 'encounter_date' },
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
    pending: 'warning',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'error',
  }

  return colors[status] || 'grey'
}

const openAddModal = () => {
  selectedEncounter.value = null
  showModal.value = true
}

const openEditModal = encounter => {
  selectedEncounter.value = encounter
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedEncounter.value = null
}

const handleSaved = () => {
  fetchEncounters()
}

const confirmDelete = encounter => {
  itemToDelete.value = encounter
  showConfirmDialog.value = true
}

const deleteEncounter = async () => {
  if (!itemToDelete.value)
    return
  deleteLoading.value = true
  try {
    await encounters.delete(itemToDelete.value.id)
    fetchEncounters()
  }
  catch (error) {
    console.error('Failed to delete encounter:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
  }
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
        <VCol
          cols="12"
          md="6"
          class="text-right"
        >
          <VBtn
            color="primary"
            @click="openAddModal"
          >
            <VIcon
              icon="tabler-plus"
              class="me-2"
            />
            Add Encounter
          </VBtn>
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
          <td>{{ encounter.chief_complaint || 'N/A' }}</td>
          <td>
            <VChip
              :color="getStatusColor(encounter.status)"
              size="small"
            >
              {{ encounter.status }}
            </VChip>
          </td>
          <td>{{ encounter.encounter_date }}</td>
          <td>
            <IconBtn @click="openEditModal(encounter)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              color="error"
              @click="confirmDelete(encounter)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </td>
        </tr>
        <tr v-if="encountersList.length === 0 && !loading">
          <td
            colspan="6"
            class="text-center text-disabled pa-4"
          >
            No encounters found
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>

  <Teleport to="body">
    <EncounterModal
      v-if="showModal"
      :encounter="selectedEncounter"
      @saved="handleSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete Encounter"
      message="Are you sure you want to delete this encounter?"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deleteEncounter"
    />
  </Teleport>

  <VOverlay
    v-model="loading"
    contained
    class="align-center justify-center"
  >
    <VProgressCircular indeterminate />
  </VOverlay>
</template>
