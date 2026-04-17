<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { encounters, patients, doctors, appointments } from '@/services/api'

const props = defineProps({
  encounter: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.encounter)
const loading = ref(false)
const refVForm = ref()

const patientsList = ref([])
const doctorsList = ref([])
const appointmentsList = ref([])

const form = ref({
  patient_id: null,
  doctor_id: null,
  appointment_id: null,
  encounter_date: '',
  type: 'consultation',
  status: 'pending',
  chief_complaint: '',
  diagnosis: '',
  examination: '',
  treatment: '',
  notes: '',
  doctor_notes: '',
})

const rules = {
  patient_id: { required: helpers.withMessage('Patient is required', required) },
  doctor_id: { required: helpers.withMessage('Doctor is required', required) },
}

const v$ = useVuelidate(rules, form)

const typeOptions = ['consultation', 'follow-up', 'emergency', 'routine']
const statusOptions = ['pending', 'in_progress', 'completed', 'cancelled']

const fetchPatients = async () => {
  try {
    const res = await patients.list()

    patientsList.value = res.data.data || res.data
  }
  catch (e) {
    console.error('Failed to fetch patients:', e)
  }
}

const fetchDoctors = async () => {
  try {
    const res = await doctors.list()

    doctorsList.value = res.data.data || res.data
  }
  catch (e) {
    console.error('Failed to fetch doctors:', e)
  }
}

const fetchAppointments = async () => {
  try {
    const res = await appointments.list({ status: 'completed' })

    appointmentsList.value = res.data.data || res.data
  }
  catch (e) {
    console.error('Failed to fetch appointments:', e)
  }
}

const initForm = () => {
  if (props.encounter) {
    form.value = {
      patient_id: props.encounter.patient_id,
      doctor_id: props.encounter.doctor_id,
      appointment_id: props.encounter.appointment_id,
      encounter_date: props.encounter.encounter_date || '',
      type: props.encounter.type || 'consultation',
      status: props.encounter.status || 'pending',
      chief_complaint: props.encounter.chief_complaint || '',
      diagnosis: props.encounter.diagnosis || '',
      examination: props.encounter.examination || '',
      treatment: props.encounter.treatment || '',
      notes: props.encounter.notes || '',
      doctor_notes: props.encounter.doctor_notes || '',
    }
  }
  else {
    form.value = {
      patient_id: null,
      doctor_id: null,
      appointment_id: null,
      encounter_date: new Date().toISOString().split('T')[0],
      type: 'consultation',
      status: 'pending',
      chief_complaint: '',
      diagnosis: '',
      examination: '',
      treatment: '',
      notes: '',
      doctor_notes: '',
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
      await encounters.update(props.encounter.id, form.value)
    }
    else {
      await encounters.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save encounter:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPatients()
  fetchDoctors()
  fetchAppointments()
  initForm()
})

watch(() => props.encounter, initForm)
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="800"
    persistent
    @click:outside="emit('closed')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ isEdit ? 'Edit Encounter' : 'Create Encounter' }}</span>
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
              <VSelect
                v-model="form.patient_id"
                label="Patient"
                :items="patientsList"
                item-title="name"
                item-value="id"
                :error-messages="v$.patient_id.$errors.length ? v$.patient_id.$errors[0].$message : []"
                @blur="v$.patient_id.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.doctor_id"
                label="Doctor"
                :items="doctorsList"
                item-title="name"
                item-value="id"
                :error-messages="v$.doctor_id.$errors.length ? v$.doctor_id.$errors[0].$message : []"
                @blur="v$.doctor_id.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.encounter_date"
                label="Encounter Date"
                type="date"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.type"
                label="Type"
                :items="typeOptions"
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
              md="6"
            >
              <AppTextField
                v-model="form.chief_complaint"
                label="Chief Complaint"
                placeholder="Primary reason for visit"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.diagnosis"
                label="Diagnosis"
                placeholder="Enter diagnosis..."
                rows="2"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.examination"
                label="Examination"
                placeholder="Physical examination findings..."
                rows="2"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.treatment"
                label="Treatment"
                placeholder="Treatment plan..."
                rows="2"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.doctor_notes"
                label="Doctor Notes"
                placeholder="Additional notes..."
                rows="2"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="General Notes"
                rows="2"
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
