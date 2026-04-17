<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, minValue, helpers } from '@vuelidate/validators'
import { services, clinics } from '@/services/api'

const props = defineProps({
  service: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.service)
const loading = ref(false)
const refVForm = ref()

const clinicsList = ref([])

const form = ref({
  name: '',
  description: '',
  duration: 30,
  price: 0,
  color: '#7367F0',
  is_active: true,
})

const rules = {
  name: { required: helpers.withMessage('Service name is required', required) },
  duration: { required: helpers.withMessage('Duration is required', required), minValue: helpers.withMessage('Duration must be at least 1 minute', minValue(1)) },
  price: { required: helpers.withMessage('Price is required', required), minValue: helpers.withMessage('Price must be positive', minValue(0)) },
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
  if (props.service) {
    form.value = {
      name: props.service.name || '',
      description: props.service.description || '',
      duration: props.service.duration || 30,
      price: props.service.price || 0,
      color: props.service.color || '#7367F0',
      is_active: props.service.is_active ?? true,
    }
  }
  else {
    form.value = {
      name: '',
      description: '',
      duration: 30,
      price: 0,
      color: '#7367F0',
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
      await services.update(props.service.id, form.value)
    }
    else {
      await services.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save service:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchClinics()
  initForm()
})

watch(() => props.service, initForm)
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="500"
    persistent
    @click:outside="emit('closed')"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ isEdit ? 'Edit Service' : 'Add Service' }}</span>
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
            <VCol cols="12">
              <AppTextField
                v-model="form.name"
                label="Service Name"
                placeholder="General Consultation"
                :error-messages="v$.name.$errors.length ? v$.name.$errors[0].$message : []"
                @blur="v$.name.$touch"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.description"
                label="Description"
                placeholder="Service description..."
                rows="3"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.duration"
                label="Duration (minutes)"
                type="number"
                placeholder="30"
                :error-messages="v$.duration.$errors.length ? v$.duration.$errors[0].$message : []"
                @blur="v$.duration.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.price"
                label="Price"
                type="number"
                prefix="$"
                placeholder="50"
                :error-messages="v$.price.$errors.length ? v$.price.$errors[0].$message : []"
                @blur="v$.price.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VColorPicker
                v-model="form.color"
                label="Color"
                mode="hex"
                :swatches="[
                  ['#7367F0', '#28C76F', '#EA5455'],
                  ['#FF9F43', '#00CFE8', '#9B59B6'],
                ]"
                show-swatches
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSwitch
                v-model="form.is_active"
                label="Active"
                color="success"
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
