<script setup>
import { VForm } from 'vuetify/components/VForm'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'
import { users, roles } from '@/services/api'

const props = defineProps({
  user: { type: Object, default: null },
})

const emit = defineEmits(['saved', 'closed'])

const isEdit = computed(() => !!props.user)
const loading = ref(false)
const refVForm = ref()

const rolesList = ref([])

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
})

const rules = {
  first_name: { required: helpers.withMessage('First name is required', required) },
  last_name: { required: helpers.withMessage('Last name is required', required) },
  email: { required: helpers.withMessage('Email is required', required), email: helpers.withMessage('Invalid email', email) },
  password: {
    required: helpers.withMessage('Password is required', required),
    minLength: helpers.withMessage('Password must be at least 6 characters', minLength(6)),
  },
  password_confirmation: {
    required: helpers.withMessage('Password confirmation is required', required),
  },
  role: { required: helpers.withMessage('Role is required', required) },
}

const v$ = useVuelidate(rules, form)

const fetchRoles = async () => {
  try {
    const response = await roles.list()

    rolesList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch roles:', error)
  }
}

const initForm = () => {
  if (props.user) {
    const nameParts = (props.user.name || '').split(' ')

    form.value = {
      first_name: nameParts[0] || '',
      last_name: nameParts.slice(1).join(' ') || '',
      email: props.user.email || '',
      password: '',
      password_confirmation: '',
      role: props.user.roles?.[0]?.name || '',
    }
  }
  else {
    form.value = {
      first_name: '',
      last_name: '',
      email: '',
      password: '',
      password_confirmation: '',
      role: '',
    }
  }
}

const save = async () => {
  const result = await v$.value.$validate()
  if (!result)
    return

  if (form.value.password !== form.value.password_confirmation) {
    console.error('Passwords do not match')
    
    return
  }

  loading.value = true
  try {
    if (isEdit.value) {
      const { password, password_confirmation, ...updateData } = form.value

      await users.update(props.user.id, updateData)
    }
    else {
      await users.create(form.value)
    }
    emit('saved')
    emit('closed')
  }
  catch (error) {
    console.error('Failed to save user:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRoles()
  initForm()
})

watch(() => props.user, initForm)
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
        <span>{{ isEdit ? 'Edit User' : 'Add User' }}</span>
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
                v-model="form.first_name"
                label="First Name"
                placeholder="John"
                :error-messages="v$.first_name.$errors.length ? v$.first_name.$errors[0].$message : []"
                @blur="v$.first_name.$touch"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.last_name"
                label="Last Name"
                placeholder="Doe"
                :error-messages="v$.last_name.$errors.length ? v$.last_name.$errors[0].$message : []"
                @blur="v$.last_name.$touch"
              />
            </VCol>

            <VCol cols="12">
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
              v-if="!isEdit"
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.password"
                label="Password"
                type="password"
                :error-messages="v$.password.$errors.length ? v$.password.$errors[0].$message : []"
                @blur="v$.password.$touch"
              />
            </VCol>

            <VCol
              v-if="!isEdit"
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="form.password_confirmation"
                label="Confirm Password"
                type="password"
                :error-messages="v$.password_confirmation.$errors.length ? v$.password_confirmation.$errors[0].$message : []"
                @blur="v$.password_confirmation.$touch"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                v-model="form.role"
                label="Role"
                :items="rolesList"
                item-title="name"
                item-value="name"
                :error-messages="v$.role.$errors.length ? v$.role.$errors[0].$message : []"
                @blur="v$.role.$touch"
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
