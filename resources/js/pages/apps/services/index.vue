<script setup>
import { services } from '@/services/api'
import ServiceModal from './ServiceModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'

definePage({
  meta: { title: 'Services' },
})

const search = ref('')
const servicesList = ref([])
const loading = ref(false)

const showModal = ref(false)
const selectedService = ref(null)

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Duration', key: 'duration' },
  { title: 'Price', key: 'price' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchServices = async () => {
  loading.value = true
  try {
    const response = await services.list({ search: search.value })

    servicesList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch services:', error)
  }
  finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedService.value = null
  showModal.value = true
}

const openEditModal = service => {
  selectedService.value = service
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedService.value = null
}

const onSaved = () => {
  fetchServices()
}

const confirmDelete = service => {
  itemToDelete.value = service
  showConfirmDialog.value = true
}

const deleteService = async () => {
  if (!itemToDelete.value)
    return

  deleteLoading.value = true
  try {
    await services.delete(itemToDelete.value.id)
    fetchServices()
  }
  catch (error) {
    console.error('Failed to delete service:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
  }
}

watch(search, fetchServices, { debounce: 300 })
onMounted(fetchServices)
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
            placeholder="Search services..."
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
            Add Service
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
          v-for="service in servicesList"
          :key="service.id"
        >
          <td>
            <div class="d-flex align-center">
              <VAvatar
                :color="service.color || 'primary'"
                size="32"
                class="me-2"
              >
                <VIcon
                  icon="tabler-briefcase"
                  size="18"
                />
              </VAvatar>
              <div>
                <span class="d-block font-weight-medium">{{ service.name }}</span>
                <span
                  v-if="service.description"
                  class="text-caption text-disabled"
                >{{ service.description.substring(0, 50) }}...</span>
              </div>
            </div>
          </td>
          <td>{{ service.duration }} min</td>
          <td>${{ service.price }}</td>
          <td>
            <VChip
              :color="service.is_active ? 'success' : 'error'"
              size="small"
            >
              {{ service.is_active ? 'Active' : 'Inactive' }}
            </VChip>
          </td>
          <td>
            <IconBtn @click="openEditModal(service)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              color="error"
              @click="confirmDelete(service)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </td>
        </tr>
        <tr v-if="servicesList.length === 0 && !loading">
          <td
            colspan="5"
            class="text-center text-disabled pa-4"
          >
            No services found
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>

  <Teleport to="body">
    <ServiceModal
      v-if="showModal"
      :service="selectedService"
      @saved="onSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete Service"
      :message="`Are you sure you want to delete ${itemToDelete?.name}?`"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deleteService"
    />
  </Teleport>
</template>
