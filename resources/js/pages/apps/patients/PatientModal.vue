<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import { patients, clinics } from '@/services/api'

const props = defineProps({
  patient: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.patient)
const loading = ref(false)
const refVForm = ref()

const clinicsList = ref([])

const form = ref({
  name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  gender: '',
  address: '',
  blood_group: '',
  allergies: '',
})

const rules = {
  name: { required: helpers.withMessage('Name is required', required) },
  email: { email: helpers.withMessage('Invalid email address', email) },
  phone: { required: helpers.withMessage('Phone is required', required) },
}

const v$ = useVuelidate(rules, form)

const genderOptions = ['male', 'female', 'other']
const bloodGroupOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']

const fetchClinics = async () => {
  try {
    const res = await clinics.list()

    clinicsList.value = res.data.data || res.data
  }
  catch (e) {
    console.error('Failed to fetch clinics:', e)
  }
}

const initForm = () => {
  if (props.patient) {
    form.value = {
      name: props.patient.name || '',
      email: props.patient.email || '',
      phone: props.patient.phone || '',
      date_of_birth: props.patient.date_of_birth || '',
      gender: props.patient.gender || '',
      address: props.patient.address || '',
      blood_group: props.patient.blood_group || '',
      allergies: props.patient.allergies || '',
    }
  }
  else {
    form.value = {
      name: '',
      email: '',
      phone: '',
      date_of_birth: '',
      gender: '',
      address: '',
      blood_group: '',
      allergies: '',
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
      await patients.update(props.patient.id, form.value)
    }
    else {
      await patients.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save patient:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchClinics()
  initForm()
})

watch(() => props.patient, initForm)
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="600"
    persistent
    @click:outside="emit('closed')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ isEdit ? 'Edit Patient' : 'Add Patient' }}</span>
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
              <AppTextField
                v-model="form.name"
                label="Full Name"
                placeholder="John Doe"
                :error-messages="v$.name.$errors.length ? v$.name.$errors[0].$message : []"
                @blur="v$.name.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.email"
                label="Email"
                placeholder="john@example.com"
                type="email"
                :error-messages="v$.email.$errors.length ? v$.email.$errors[0].$message : []"
                @blur="v$.email.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.phone"
                label="Phone"
                placeholder="+1234567890"
                :error-messages="v$.phone.$errors.length ? v$.phone.$errors[0].$message : []"
                @blur="v$.phone.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.date_of_birth"
                label="Date of Birth"
                type="date"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.gender"
                label="Gender"
                :items="genderOptions"
                placeholder="Select gender"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.blood_group"
                label="Blood Group"
                :items="bloodGroupOptions"
                placeholder="Select blood group"
                clearable
              />
            </VCol>

            <VCol cols="12">
              <AppTextField
                v-model="form.address"
                label="Address"
                placeholder="123 Main St, City, Country"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.allergies"
                label="Allergies"
                placeholder="List any known allergies..."
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
