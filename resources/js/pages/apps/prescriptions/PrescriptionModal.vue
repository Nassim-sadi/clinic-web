<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { prescriptions, patients, doctors, encounters } from '@/services/api'

const props = defineProps({
  prescription: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.prescription)
const loading = ref(false)
const refVForm = ref()

const patientsList = ref([])
const doctorsList = ref([])
const encountersList = ref([])

const form = ref({
  patient_id: null,
  doctor_id: null,
  encounter_id: null,
  medicine: '',
  dosage: '',
  frequency: '',
  duration: '',
  instructions: '',
  notes: '',
  is_active: true,
  status: 'active',
})

const rules = {
  patient_id: { required: helpers.withMessage('Patient is required', required) },
  doctor_id: { required: helpers.withMessage('Doctor is required', required) },
  medicine: { required: helpers.withMessage('Medicine is required', required) },
}

const v$ = useVuelidate(rules, form)

const statusOptions = ['active', 'completed', 'cancelled']
const frequencyOptions = ['once daily', 'twice daily', 'three times daily', 'four times daily', 'every 4 hours', 'every 6 hours', 'every 8 hours', 'as needed']

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

const fetchEncounters = async () => {
  try {
    const res = await encounters.list()

    encountersList.value = res.data.data || res.data
  }
  catch (e) {
    console.error('Failed to fetch encounters:', e)
  }
}

const initForm = () => {
  if (props.prescription) {
    form.value = {
      patient_id: props.prescription.patient_id,
      doctor_id: props.prescription.doctor_id,
      encounter_id: props.prescription.encounter_id,
      medicine: props.prescription.medicine || '',
      dosage: props.prescription.dosage || '',
      frequency: props.prescription.frequency || '',
      duration: props.prescription.duration || '',
      instructions: props.prescription.instructions || '',
      notes: props.prescription.notes || '',
      is_active: props.prescription.is_active ?? true,
      status: props.prescription.status || 'active',
    }
  }
  else {
    form.value = {
      patient_id: null,
      doctor_id: null,
      encounter_id: null,
      medicine: '',
      dosage: '',
      frequency: '',
      duration: '',
      instructions: '',
      notes: '',
      is_active: true,
      status: 'active',
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
      await prescriptions.update(props.prescription.id, form.value)
    }
    else {
      await prescriptions.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save prescription:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPatients()
  fetchDoctors()
  fetchEncounters()
  initForm()
})

watch(() => props.prescription, initForm)
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
        <span>{{ isEdit ? 'Edit Prescription' : 'Create Prescription' }}</span>
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
                v-model="form.medicine"
                label="Medicine"
                placeholder="Enter medicine name"
                :error-messages="v$.medicine.$errors.length ? v$.medicine.$errors[0].$message : []"
                @blur="v$.medicine.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.dosage"
                label="Dosage"
                placeholder="e.g., 500mg"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.frequency"
                label="Frequency"
                :items="frequencyOptions"
                clearable
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.duration"
                label="Duration"
                placeholder="e.g., 7 days"
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
              <VSwitch
                v-model="form.is_active"
                label="Active"
                color="primary"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.instructions"
                label="Instructions"
                placeholder="Special instructions for the patient..."
                rows="2"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Notes"
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
