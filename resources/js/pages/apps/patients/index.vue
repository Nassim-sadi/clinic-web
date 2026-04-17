<script setup>
import { patients } from '@/services/api'
import PatientModal from './PatientModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'

definePage({
  meta: { title: 'Patients' },
})

const search = ref('')
const patientsList = ref([])
const loading = ref(false)

const pagination = ref({
  page: 1,
  itemsPerPage: 10,
  total: 0,
})

const showModal = ref(false)
const selectedPatient = ref(null)

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Phone', key: 'phone' },
  { title: 'Gender', key: 'gender' },
  { title: 'Date of Birth', key: 'date_of_birth' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchPatients = async () => {
  loading.value = true
  try {
    const response = await patients.list({
      page: pagination.value.page,
      per_page: pagination.value.itemsPerPage,
      search: search.value,
    })

    patientsList.value = response.data.data || response.data
    pagination.value.total = response.data.total || patientsList.value.length
  }
  catch (error) {
    console.error('Failed to fetch patients:', error)
  }
  finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedPatient.value = null
  showModal.value = true
}

const openEditModal = patient => {
  selectedPatient.value = patient
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedPatient.value = null
}

const onSaved = () => {
  fetchPatients()
}

const confirmDelete = patient => {
  itemToDelete.value = patient
  showConfirmDialog.value = true
}

const deletePatient = async () => {
  if (!itemToDelete.value)
    return

  deleteLoading.value = true
  try {
    await patients.delete(itemToDelete.value.id)
    fetchPatients()
  }
  catch (error) {
    console.error('Failed to delete patient:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
  }
}

watch([() => pagination.value.page, () => pagination.value.itemsPerPage], fetchPatients)
watch(search, () => {
  pagination.value.page = 1
  fetchPatients()
}, { debounce: 300 })

onMounted(fetchPatients)
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
            placeholder="Search patients..."
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
            @click="openCreateModal"
          >
            <VIcon
              icon="tabler-plus"
              class="me-2"
            />
            Add Patient
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
          v-for="patient in patientsList"
          :key="patient.id"
        >
          <td>{{ patient.name }}</td>
          <td>{{ patient.email || 'N/A' }}</td>
          <td>{{ patient.phone || 'N/A' }}</td>
          <td>{{ patient.gender || 'N/A' }}</td>
          <td>{{ patient.date_of_birth || 'N/A' }}</td>
          <td>
            <IconBtn @click="openEditModal(patient)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              color="error"
              @click="confirmDelete(patient)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </td>
        </tr>
        <tr v-if="patientsList.length === 0 && !loading">
          <td
            colspan="6"
            class="text-center text-disabled pa-4"
          >
            No patients found
          </td>
        </tr>
      </tbody>
    </VTable>

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
    <PatientModal
      v-if="showModal"
      :patient="selectedPatient"
      @saved="onSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete Patient"
      :message="`Are you sure you want to delete ${itemToDelete?.name}?`"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deletePatient"
    />
  </Teleport>
</template>
