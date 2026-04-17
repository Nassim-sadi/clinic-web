<script setup>
import { api } from '@/services/api'

definePage({
  meta: { title: 'Reports' },
})

const reportType = ref('appointments')
const dateFrom = ref('')
const dateTo = ref('')
const loading = ref(false)
const reportData = ref(null)

const reportTypes = [
  { title: 'Appointments', value: 'appointments' },
  { title: 'Patients', value: 'patients' },
  { title: 'Revenue', value: 'revenue' },
  { title: 'Doctors', value: 'doctors' },
]

const fetchReport = async () => {
  loading.value = true
  try {
    const response = await api.get('/reports', {
      params: {
        type: reportType.value,
        from: dateFrom.value,
        to: dateTo.value,
      },
    })

    reportData.value = response.data
  }
  catch (error) {
    console.error('Failed to fetch report:', error)
  }
  finally {
    loading.value = false
  }
}

const exportReport = async () => {
  loading.value = true
  try {
    await api.get('/reports/export', {
      params: {
        type: reportType.value,
        from: dateFrom.value,
        to: dateTo.value,
        format: 'csv',
      },
      responseType: 'blob',
    })
  }
  catch (error) {
    console.error('Failed to export report:', error)
  }
  finally {
    loading.value = false
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="3"
            >
              <VSelect
                v-model="reportType"
                :items="reportTypes"
                label="Report Type"
                density="compact"
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <AppTextField
                v-model="dateFrom"
                label="From Date"
                type="date"
                density="compact"
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
            >
              <AppTextField
                v-model="dateTo"
                label="To Date"
                type="date"
                density="compact"
              />
            </VCol>
            <VCol
              cols="12"
              md="3"
              class="d-flex align-center gap-2"
            >
              <VBtn
                color="primary"
                :loading="loading"
                @click="fetchReport"
              >
                Generate
              </VBtn>
              <VBtn
                variant="outlined"
                :loading="loading"
                @click="exportReport"
              >
                Export CSV
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      v-if="reportData"
      cols="12"
    >
      <VCard>
        <VCardText>
          <h5 class="mb-4">
            Report Summary
          </h5>
          <pre>{{ JSON.stringify(reportData, null, 2) }}</pre>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="3"
    >
      <VCard>
        <VCardText class="text-center">
          <h2 class="text-h2 text-primary">
            {{ reportData?.total || 0 }}
          </h2>
          <p class="text-body-1 text-disabled">
            Total Records
          </p>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
