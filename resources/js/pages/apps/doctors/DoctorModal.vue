<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minValue, helpers } from '@vuelidate/validators'
import { doctors, clinics } from '@/services/api'

const props = defineProps({
  doctor: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.doctor)
const loading = ref(false)
const refVForm = ref()

const clinicsList = ref([])

const form = ref({
  name: '',
  email: '',
  phone: '',
  specialty: '',
  qualification: '',
  experience_years: null,
  bio: '',
  consultation_fee: null,
  is_active: true,
})

const rules = {
  name: { required: helpers.withMessage('Name is required', required) },
  email: { email: helpers.withMessage('Invalid email address', email) },
  phone: { required: helpers.withMessage('Phone is required', required) },
  specialty: { required: helpers.withMessage('Specialty is required', required) },
  consultation_fee: { minValue: helpers.withMessage('Fee must be positive', minValue(0)) },
}

const v$ = useVuelidate(rules, form)

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
  if (props.doctor) {
    form.value = {
      name: props.doctor.name || '',
      email: props.doctor.email || '',
      phone: props.doctor.phone || '',
      specialty: props.doctor.specialty || '',
      qualification: props.doctor.qualification || '',
      experience_years: props.doctor.experience_years || null,
      bio: props.doctor.bio || '',
      consultation_fee: props.doctor.consultation_fee || null,
      is_active: props.doctor.is_active ?? true,
    }
  }
  else {
    form.value = {
      name: '',
      email: '',
      phone: '',
      specialty: '',
      qualification: '',
      experience_years: null,
      bio: '',
      consultation_fee: null,
      is_active: true,
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
      await doctors.update(props.doctor.id, form.value)
    }
    else {
      await doctors.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save doctor:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchClinics()
  initForm()
})

watch(() => props.doctor, initForm)
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
        <span>{{ isEdit ? 'Edit Doctor' : 'Add Doctor' }}</span>
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
                placeholder="Dr. John Smith"
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
                placeholder="doctor@example.com"
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
                v-model="form.specialty"
                label="Specialty"
                placeholder="General Physician"
                :error-messages="v$.specialty.$errors.length ? v$.specialty.$errors[0].$message : []"
                @blur="v$.specialty.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.qualification"
                label="Qualification"
                placeholder="MD, MBBS"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.experience_years"
                label="Years of Experience"
                type="number"
                placeholder="5"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.consultation_fee"
                label="Consultation Fee"
                type="number"
                prefix="$"
                placeholder="50"
                :error-messages="v$.consultation_fee.$errors.length ? v$.consultation_fee.$errors[0].$message : []"
                @blur="v$.consultation_fee.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VRow>
                <VCol cols="6">
                  <VCheckbox
                    v-model="form.is_active"
                    label="Active"
                    color="success"
                    density="compact"
                    hide-details
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.bio"
                label="Bio"
                placeholder="Brief description about the doctor..."
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
