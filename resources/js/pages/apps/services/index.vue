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

    <VDataTable
      :headers="headers"
      :items="servicesList"
      :loading="loading"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center">
          <VAvatar
            :color="item.color || 'primary'"
            size="32"
            class="me-2"
          >
            <VIcon
              icon="tabler-briefcase"
              size="18"
            />
          </VAvatar>
          <div>
            <span class="d-block font-weight-medium">{{ item.name }}</span>
            <span
              v-if="item.description"
              class="text-caption text-disabled"
            >{{ item.description.substring(0, 50) }}...</span>
          </div>
        </div>
      </template>
      <template #item.duration="{ item }">
        {{ item.duration }} min
      </template>
      <template #item.price="{ item }">
        ${{ item.price }}
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
