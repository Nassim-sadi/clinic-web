<script setup>
import { bills } from '@/services/api'
import BillModal from './BillModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'

definePage({
  meta: { title: 'Billing' },
})

const search = ref('')
const billsList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedBill = ref(null)

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Invoice #', key: 'invoice_number' },
  { title: 'Patient', key: 'patient.name' },
  { title: 'Amount', key: 'total_amount' },
  { title: 'Status', key: 'status' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchBills = async () => {
  loading.value = true
  try {
    const response = await bills.list({ search: search.value })

    billsList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch bills:', error)
  }
  finally {
    loading.value = false
  }
}

const getStatusColor = status => {
  const colors = {
    draft: 'grey',
    sent: 'info',
    pending: 'warning',
    paid: 'success',
    partial: 'warning',
    cancelled: 'error',
  }

  return colors[status] || 'grey'
}

const formatCurrency = value => `$${value || 0}`

const openAddModal = () => {
  selectedBill.value = null
  showModal.value = true
}

const openEditModal = bill => {
  selectedBill.value = bill
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedBill.value = null
}

const handleSaved = () => {
  fetchBills()
}

const confirmDelete = bill => {
  itemToDelete.value = bill
  showConfirmDialog.value = true
}

const deleteBill = async () => {
  if (!itemToDelete.value)
    return
  deleteLoading.value = true
  try {
    await bills.delete(itemToDelete.value.id)
    fetchBills()
  }
  catch (error) {
    console.error('Failed to delete bill:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
  }
}

watch(search, fetchBills, { debounce: 300 })
onMounted(fetchBills)
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
            placeholder="Search bills..."
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
            Create Bill
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>

    <VDataTable
      :headers="headers"
      :items="billsList"
      :loading="loading"
    >
      <template #item.total_amount="{ item }">
        {{ formatCurrency(item.total_amount) }}
      </template>
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
    <BillModal
      v-if="showModal"
      :bill="selectedBill"
      @saved="handleSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete Bill"
      :message="`Are you sure you want to delete invoice ${itemToDelete?.invoice_number}?`"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deleteBill"
    />
  </Teleport>
</template>
