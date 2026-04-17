<script setup>
import { bills } from '@/services/api'

definePage({
  meta: { title: 'Billing' },
})

const search = ref('')
const billsList = ref([])
const loading = ref(false)

const headers = [
  { title: 'Invoice #', key: 'invoice_number' },
  { title: 'Patient', key: 'patient.name' },
  { title: 'Amount', key: 'total' },
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
    pending: 'warning',
    paid: 'success',
    cancelled: 'error',
  }

  
  return colors[status] || 'grey'
}

const formatCurrency = value => `$${value || 0}`

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
          <VBtn color="primary">
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
          <td>{{ formatCurrency(bill.total) }}</td>
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
            <VBtn
              icon
              variant="text"
              size="small"
            >
              <VIcon icon="tabler-eye" />
            </VBtn>
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
</template>
