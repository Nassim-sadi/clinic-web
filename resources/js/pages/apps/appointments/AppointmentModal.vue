<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { appointments, doctors, patients, services } from '@/services/api'

const props = defineProps({
  appointment: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.appointment)
const loading = ref(false)
const refVForm = ref()

const doctorsList = ref([])
const patientsList = ref([])
const servicesList = ref([])

const statusOptions = [
  { title: 'Scheduled', value: 'scheduled' },
  { title: 'Confirmed', value: 'confirmed' },
  { title: 'Completed', value: 'completed' },
  { title: 'Cancelled', value: 'cancelled' },
  { title: 'No Show', value: 'no_show' },
]

const form = ref({
  doctor_id: null,
  patient_id: null,
  service_id: null,
  date: '',
  start_time: '',
  end_time: '',
  status: 'scheduled',
  notes: '',
  type: 'online',
})

const rules = {
  doctor_id: { required: helpers.withMessage('Doctor is required', required) },
  patient_id: { required: helpers.withMessage('Patient is required', required) },
  date: { required: helpers.withMessage('Date is required', required) },
  start_time: { required: helpers.withMessage('Start time is required', required) },
  end_time: { required: helpers.withMessage('End time is required', required) },
}

const v$ = useVuelidate(rules, form)

const fetchData = async () => {
  try {
    const [doctorsRes, patientsRes, servicesRes] = await Promise.all([
      doctors.list({ is_active: true }),
      patients.list({ per_page: 100 }),
      services.list(),
    ])

    doctorsList.value = doctorsRes.data.data || doctorsRes.data
    patientsList.value = patientsRes.data.data || patientsRes.data
    servicesList.value = servicesRes.data.data || servicesRes.data
  }
  catch (e) {
    console.error('Failed to fetch data:', e)
  }
}

const initForm = () => {
  if (props.appointment) {
    form.value = {
      doctor_id: props.appointment.doctor_id || null,
      patient_id: props.appointment.patient_id || null,
      service_id: props.appointment.service_id || null,
      date: props.appointment.date || '',
      start_time: props.appointment.start_time || '',
      end_time: props.appointment.end_time || '',
      status: props.appointment.status || 'scheduled',
      notes: props.appointment.notes || '',
      type: props.appointment.type || 'online',
    }
  }
  else {
    form.value = {
      doctor_id: null,
      patient_id: null,
      service_id: null,
      date: new Date().toISOString().split('T')[0],
      start_time: '',
      end_time: '',
      status: 'scheduled',
      notes: '',
      type: 'online',
    }
  }
}

const save = async () => {
  const result = await v$.value.$validate()
  if (!result)
    return

  loading.value = true
  try {
    if (isEdit.value) {
      await appointments.update(props.appointment.id, form.value)
    }
    else {
      await appointments.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save appointment:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
  initForm()
})

watch(() => props.appointment, initForm)
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="700"
    persistent
    @click:outside="emit('closed')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ isEdit ? 'Edit Appointment' : 'New Appointment' }}</span>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="emit('closed')"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VForm
          ref="refVForm"
          @submit.prevent="save"
        >
          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <VAutocomplete
                v-model="form.patient_id"
                label="Patient"
                :items="patientsList"
                item-title="name"
                item-value="id"
                placeholder="Select patient"
                :error-messages="v$.patient_id.$errors.length ? v$.patient_id.$errors[0].$message : []"
                @blur="v$.patient_id.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VAutocomplete
                v-model="form.doctor_id"
                label="Doctor"
                :items="doctorsList"
                item-title="name"
                item-value="id"
                placeholder="Select doctor"
                :error-messages="v$.doctor_id.$errors.length ? v$.doctor_id.$errors[0].$message : []"
                @blur="v$.doctor_id.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VAutocomplete
                v-model="form.service_id"
                label="Service"
                :items="servicesList"
                item-title="name"
                item-value="id"
                placeholder="Select service"
                clearable
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.status"
                label="Status"
                :items="statusOptions"
              />
            </VCol>

            <VCol
              cols="12"
              md="4"
            >
              <AppTextField
                v-model="form.date"
                label="Date"
                type="date"
                :error-messages="v$.date.$errors.length ? v$.date.$errors[0].$message : []"
                @blur="v$.date.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="4"
            >
              <AppTimePicker
                v-model="form.start_time"
                label="Start Time"
                :error-messages="v$.start_time.$errors.length ? v$.start_time.$errors[0].$message : []"
                @blur="v$.start_time.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="4"
            >
              <AppTimePicker
                v-model="form.end_time"
                label="End Time"
                :error-messages="v$.end_time.$errors.length ? v$.end_time.$errors[0].$message : []"
                @blur="v$.end_time.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.type"
                label="Appointment Type"
                :items="[
                  { title: 'Online', value: 'online' },
                  { title: 'In-Person', value: 'offline' },
                ]"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Notes"
                placeholder="Additional notes..."
                rows="3"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex justify-end gap-2">
        <VBtn
          variant="outlined"
          @click="emit('closed')"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="save"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
