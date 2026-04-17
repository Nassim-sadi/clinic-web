<script setup>
import { prescriptions } from '@/services/api'
import PrescriptionModal from './PrescriptionModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'

definePage({
  meta: { title: 'Prescriptions' },
})

const search = ref('')
const prescriptionsList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedPrescription = ref(null)

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Patient', key: 'patient.name' },
  { title: 'Doctor', key: 'doctor.name' },
  { title: 'Medicine', key: 'medicine' },
  { title: 'Status', key: 'status' },
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

const getStatusColor = status => {
  const colors = {
    active: 'success',
    completed: 'info',
    cancelled: 'error',
  }

  return colors[status] || 'grey'
}

const openAddModal = () => {
  selectedPrescription.value = null
  showModal.value = true
}

const openEditModal = prescription => {
  selectedPrescription.value = prescription
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedPrescription.value = null
}

const handleSaved = () => {
  fetchPrescriptions()
}

const confirmDelete = prescription => {
  itemToDelete.value = prescription
  showConfirmDialog.value = true
}

const deletePrescription = async () => {
  if (!itemToDelete.value)
    return
  deleteLoading.value = true
  try {
    await prescriptions.delete(itemToDelete.value.id)
    fetchPrescriptions()
  }
  catch (error) {
    console.error('Failed to delete prescription:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
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
            Add Prescription
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>

    <VDataTable
      :headers="headers"
      :items="prescriptionsList"
      :loading="loading"
    >
      <template #item.status="{ item }">
        <VChip
          :color="getStatusColor(item.status)"
          size="small"
        >
          {{ item.status }}
        </VChip>
      </template>
      <template #item.actions="{ item }">
        <IconBtn @click="openEditModal(item)">
          <VIcon icon="tabler-edit" />
        </IconBtn>
        <IconBtn @click="prescriptions.download(item.id)">
          <VIcon icon="tabler-download" />
        </IconBtn>
        <IconBtn
          color="error"
          @click="confirmDelete(item)"
        >
          <VIcon icon="tabler-trash" />
        </IconBtn>
      </template>
    </VDataTable>
  </VCard>

  <Teleport to="body">
    <PrescriptionModal
      v-if="showModal"
      :prescription="selectedPrescription"
      @saved="handleSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete Prescription"
      message="Are you sure you want to delete this prescription?"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deletePrescription"
    />
  </Teleport>
</template>
