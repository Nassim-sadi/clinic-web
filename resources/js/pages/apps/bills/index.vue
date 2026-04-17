<script setup>
import { bills } from '@/services/api'
import BillModal from './BillModal.vue'

definePage({
  meta: { title: 'Billing' },
})

const search = ref('')
const billsList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedBill = ref(null)

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

const deleteBill = async bill => {
  if (!confirm(`Delete bill ${bill.invoice_number}?`))
    return
  try {
    await bills.delete(bill.id)
    fetchBills()
  }
  catch (error) {
    console.error('Failed to delete bill:', error)
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
          v-for="bill in billsList"
          :key="bill.id"
        >
          <td>{{ bill.invoice_number }}</td>
          <td>{{ bill.patient?.name || 'N/A' }}</td>
          <td>{{ formatCurrency(bill.total_amount) }}</td>
          <td>
            <VChip
              :color="getStatusColor(bill.status)"
              size="small"
            >
              {{ bill.status }}
            </VChip>
          </td>
          <td>{{ bill.created_at }}</td>
          <td>
            <IconBtn @click="openEditModal(bill)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn @click="deleteBill(bill)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </td>
        </tr>
        <tr v-if="billsList.length === 0 && !loading">
          <td
            colspan="6"
            class="text-center text-disabled pa-4"
          >
            No bills found
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>

  <BillModal
    v-if="showModal"
    :bill="selectedBill"
    @saved="handleSaved"
    @closed="closeModal"
  />

  <VOverlay
    v-model="loading"
    contained
    class="align-center justify-center"
  >
    <VProgressCircular indeterminate />
  </VOverlay>
</template>
