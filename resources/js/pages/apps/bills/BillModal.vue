<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers, decimal } from '@vuelidate/validators'
import { bills, patients, doctors, encounters } from '@/services/api'

const props = defineProps({
  bill: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.bill)
const loading = ref(false)
const refVForm = ref()

const patientsList = ref([])
const doctorsList = ref([])
const encountersList = ref([])

const form = ref({
  patient_id: null,
  doctor_id: null,
  encounter_id: null,
  subtotal: 0,
  discount: 0,
  tax: 0,
  total_amount: 0,
  amount_paid: 0,
  status: 'draft',
  payment_status: 'unpaid',
  payment_method: '',
  notes: '',
  due_date: '',
})

const rules = {
  patient_id: { required: helpers.withMessage('Patient is required', required) },
  subtotal: { decimal: helpers.withMessage('Must be a valid number', decimal) },
  total_amount: { decimal: helpers.withMessage('Must be a valid number', decimal) },
}

const v$ = useVuelidate(rules, form)

const statusOptions = ['draft', 'sent', 'paid', 'partial', 'cancelled']
const paymentStatusOptions = ['unpaid', 'pending', 'paid', 'refunded']
const paymentMethodOptions = ['cash', 'card', 'insurance', 'bank_transfer']

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
    const res = await encounters.list({ status: 'completed' })

    encountersList.value = res.data.data || res.data
  }
  catch (e) {
    console.error('Failed to fetch encounters:', e)
  }
}

const calculateTotal = () => {
  const subtotal = parseFloat(form.value.subtotal) || 0
  const discount = parseFloat(form.value.discount) || 0
  const tax = parseFloat(form.value.tax) || 0

  form.value.total_amount = subtotal - discount + tax
}

watch(() => [form.value.subtotal, form.value.discount, form.value.tax], calculateTotal)

const initForm = () => {
  if (props.bill) {
    form.value = {
      patient_id: props.bill.patient_id,
      doctor_id: props.bill.doctor_id,
      encounter_id: props.bill.encounter_id,
      subtotal: props.bill.subtotal || 0,
      discount: props.bill.discount || 0,
      tax: props.bill.tax || 0,
      total_amount: props.bill.total_amount || 0,
      amount_paid: props.bill.amount_paid || 0,
      status: props.bill.status || 'draft',
      payment_status: props.bill.payment_status || 'unpaid',
      payment_method: props.bill.payment_method || '',
      notes: props.bill.notes || '',
      due_date: props.bill.due_date || '',
    }
  }
  else {
    form.value = {
      patient_id: null,
      doctor_id: null,
      encounter_id: null,
      subtotal: 0,
      discount: 0,
      tax: 0,
      total_amount: 0,
      amount_paid: 0,
      status: 'draft',
      payment_status: 'unpaid',
      payment_method: '',
      notes: '',
      due_date: '',
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
      await bills.update(props.bill.id, form.value)
    }
    else {
      await bills.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save bill:', error)
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

watch(() => props.bill, initForm)
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
        <span>{{ isEdit ? 'Edit Bill' : 'Create Bill' }}</span>
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
              md="6"
            >
              <VSelect
                v-model="form.payment_status"
                label="Payment Status"
                :items="paymentStatusOptions"
              />
            </VCol>

            <VCol
              cols="12"
              md="4"
            >
              <AppTextField
                v-model="form.subtotal"
                label="Subtotal"
                type="number"
                prefix="$"
              />
            </VCol>

            <VCol
              cols="12"
              md="4"
            >
              <AppTextField
                v-model="form.discount"
                label="Discount"
                type="number"
                prefix="$"
              />
            </VCol>

            <VCol
              cols="12"
              md="4"
            >
              <AppTextField
                v-model="form.tax"
                label="Tax"
                type="number"
                prefix="$"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.total_amount"
                label="Total Amount"
                type="number"
                prefix="$"
                disabled
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.amount_paid"
                label="Amount Paid"
                type="number"
                prefix="$"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.payment_method"
                label="Payment Method"
                :items="paymentMethodOptions"
                clearable
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.due_date"
                label="Due Date"
                type="date"
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
