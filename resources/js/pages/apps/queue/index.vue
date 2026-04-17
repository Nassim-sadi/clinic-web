<script setup>
import { waitingQueue } from '@/services/api'

definePage({
  meta: { title: 'Waiting Queue' },
})

const queueList = ref([])

const stats = ref({
  waiting: 0,
  called: 0,
  in_progress: 0,
})

const loading = ref(false)

const fetchQueue = async () => {
  loading.value = true
  try {
    const [queueRes, statsRes] = await Promise.all([
      waitingQueue.list(),
      waitingQueue.stats(),
    ])

    queueList.value = queueRes.data.data || queueRes.data
    stats.value = statsRes.data
  }
  catch (error) {
    console.error('Failed to fetch queue:', error)
  }
  finally {
    loading.value = false
  }
}

const callPatient = async id => {
  try {
    await waitingQueue.call(id)
    fetchQueue()
  }
  catch (error) {
    console.error('Failed to call patient:', error)
  }
}

const startEncounter = async id => {
  try {
    await waitingQueue.start(id)
    fetchQueue()
  }
  catch (error) {
    console.error('Failed to start encounter:', error)
  }
}

const completePatient = async id => {
  try {
    await waitingQueue.complete(id)
    fetchQueue()
  }
  catch (error) {
    console.error('Failed to complete:', error)
  }
}

const getStatusColor = status => {
  const colors = {
    waiting: 'info',
    called: 'warning',
    in_progress: 'primary',
    completed: 'success',
  }

  
  return colors[status] || 'grey'
}

onMounted(fetchQueue)
</script>

<template>
  <VRow>
    <VCol
      cols="12"
      md="4"
    >
      <VCard>
        <VCardText class="text-center">
          <VAvatar
            color="info"
            variant="tonal"
            :size="64"
            class="mb-2"
          >
            <VIcon
              icon="tabler-users"
              size="32"
            />
          </VAvatar>
          <h2 class="text-h2">
            {{ stats.waiting }}
          </h2>
          <p class="text-body-1 text-disabled">
            Waiting
          </p>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <VCard>
        <VCardText class="text-center">
          <VAvatar
            color="warning"
            variant="tonal"
            :size="64"
            class="mb-2"
          >
            <VIcon
              icon="tabler-bell"
              size="32"
            />
          </VAvatar>
          <h2 class="text-h2">
            {{ stats.called }}
          </h2>
          <p class="text-body-1 text-disabled">
            Called
          </p>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <VCard>
        <VCardText class="text-center">
          <VAvatar
            color="primary"
            variant="tonal"
            :size="64"
            class="mb-2"
          >
            <VIcon
              icon="tabler-stethoscope"
              size="32"
            />
          </VAvatar>
          <h2 class="text-h2">
            {{ stats.in_progress }}
          </h2>
          <p class="text-body-1 text-disabled">
            In Progress
          </p>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <VCard>
        <VCardText>
          <h5 class="mb-4">
            Queue List
          </h5>

          <VDataTable
            :headers="[
              { title: '#', key: 'index' },
              { title: 'Patient', key: 'patient.name' },
              { title: 'Doctor', key: 'doctor.name' },
              { title: 'Service', key: 'service.name' },
              { title: 'Status', key: 'status' },
              { title: 'Actions', key: 'actions' },
            ]"
            :items="queueList"
            :loading="loading"
          >
            <template #item.index="{ index }">
              {{ index + 1 }}
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
              <VBtn
                v-if="item.status === 'waiting'"
                size="small"
                color="warning"
                variant="tonal"
                @click="callPatient(item.id)"
              >
                Call
              </VBtn>
              <VBtn
                v-if="item.status === 'called'"
                size="small"
                color="primary"
                variant="tonal"
                @click="startEncounter(item.id)"
              >
                Start
              </VBtn>
              <VBtn
                v-if="item.status === 'in_progress'"
                size="small"
                color="success"
                variant="tonal"
                @click="completePatient(item.id)"
              >
                Complete
              </VBtn>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
