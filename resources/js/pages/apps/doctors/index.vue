<script setup>
import { doctors } from '@/services/api'
import DoctorModal from './DoctorModal.vue'

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

const deleteDoctor = async id => {
  if (confirm('Are you sure you want to delete this doctor?')) {
    try {
      await doctors.delete(id)
      fetchDoctors()
    }
    catch (error) {
      console.error('Failed to delete doctor:', error)
    }
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
          v-for="doctor in doctorsList"
          :key="doctor.id"
        >
          <td>{{ doctor.name }}</td>
          <td>{{ doctor.email || 'N/A' }}</td>
          <td>{{ doctor.phone || 'N/A' }}</td>
          <td>{{ doctor.specialty || 'N/A' }}</td>
          <td>{{ doctor.experience_years ? `${doctor.experience_years} years` : 'N/A' }}</td>
          <td>
            <VChip
              :color="doctor.is_active ? 'success' : 'error'"
              size="small"
            >
              {{ doctor.is_active ? 'Active' : 'Inactive' }}
            </VChip>
          </td>
          <td>
            <VBtn
              icon
              variant="text"
              size="small"
              @click="openEditModal(doctor)"
            >
              <VIcon icon="tabler-edit" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              color="error"
              @click="deleteDoctor(doctor.id)"
            >
              <VIcon icon="tabler-trash" />
            </VBtn>
          </td>
        </tr>
        <tr v-if="doctorsList.length === 0 && !loading">
          <td
            colspan="7"
            class="text-center text-disabled pa-4"
          >
            No doctors found
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
    <DoctorModal
      v-if="showModal"
      :doctor="selectedDoctor"
      @saved="onSaved"
      @closed="closeModal"
    />
  </Teleport>
</template>
