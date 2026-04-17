<script setup>
import { doctors } from '@/services/api'
import DoctorModal from './DoctorModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'

definePage({
  meta: { title: 'Doctors' },
})

const search = ref('')
const doctorsList = ref([])
const loading = ref(false)

const pagination = ref({
  page: 1,
  itemsPerPage: 10,
  total: 0,
})

const showModal = ref(false)
const selectedDoctor = ref(null)

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Phone', key: 'phone' },
  { title: 'Specialty', key: 'specialty' },
  { title: 'Experience', key: 'experience_years' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchDoctors = async () => {
  loading.value = true
  try {
    const response = await doctors.list({
      page: pagination.value.page,
      per_page: pagination.value.itemsPerPage,
      search: search.value,
    })

    doctorsList.value = response.data.data || response.data
    pagination.value.total = response.data.total || doctorsList.value.length
  }
  catch (error) {
    console.error('Failed to fetch doctors:', error)
  }
  finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedDoctor.value = null
  showModal.value = true
}

const openEditModal = doctor => {
  selectedDoctor.value = doctor
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedDoctor.value = null
}

const onSaved = () => {
  fetchDoctors()
}

const confirmDelete = doctor => {
  itemToDelete.value = doctor
  showConfirmDialog.value = true
}

const deleteDoctor = async () => {
  if (!itemToDelete.value)
    return

  deleteLoading.value = true
  try {
    await doctors.delete(itemToDelete.value.id)
    fetchDoctors()
  }
  catch (error) {
    console.error('Failed to delete doctor:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
  }
}

watch([() => pagination.value.page, () => pagination.value.itemsPerPage], fetchDoctors)
watch(search, () => {
  pagination.value.page = 1
  fetchDoctors()
}, { debounce: 300 })

onMounted(fetchDoctors)
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
            placeholder="Search doctors..."
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
            Add Doctor
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>

    <VDataTable
      :headers="headers"
      :items="doctorsList"
      :loading="loading"
      :items-length="pagination.total"
    >
      <template #item.experience_years="{ item }">
        {{ item.experience_years ? `${item.experience_years} years` : 'N/A' }}
      </template>
      <template #item.is_active="{ item }">
        <VChip
          :color="item.is_active ? 'success' : 'error'"
          size="small"
        >
          {{ item.is_active ? 'Active' : 'Inactive' }}
        </VChip>
      </template>
      <template #item.actions="{ item }">
        <IconBtn @click="openEditModal(item)">
          <VIcon icon="tabler-edit" />
        </IconBtn>
        <IconBtn
          color="error"
          @click="confirmDelete(item)"
        >
          <VIcon icon="tabler-trash" />
        </IconBtn>
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
    <DoctorModal
      v-if="showModal"
      :doctor="selectedDoctor"
      @saved="onSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete Doctor"
      :message="`Are you sure you want to delete ${itemToDelete?.name}?`"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deleteDoctor"
    />
  </Teleport>
</template>
