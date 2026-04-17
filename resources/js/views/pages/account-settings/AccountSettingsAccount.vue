<script setup>
import { useAuthStore } from '@/stores/auth'
import { auth } from '@/services/api'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const refInputEl = ref()
const isConfirmDialogOpen = ref(false)
const isAccountDeactivated = ref(false)
const validateAccountDeactivation = [v => !!v || 'Please confirm account deactivation']

const accountDataLocal = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
})

const fetchUser = () => {
  if (user.value) {
    accountDataLocal.value = {
      firstName: user.value.first_name || '',
      lastName: user.value.last_name || '',
      email: user.value.email || '',
      phone: user.value.phone || '',
    }
  }
}

const refForm = ref()

const onSubmit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid)
    return

  try {
    await auth.updateProfile({
      first_name: accountDataLocal.value.firstName,
      last_name: accountDataLocal.value.lastName,
      phone: accountDataLocal.value.phone,
    })
    authStore.fetchUser()
  }
  catch (error) {
    console.error('Failed to update profile:', error)
  }
}

const resetForm = () => {
  fetchUser()
}

const changeAvatar = file => {
  const fileReader = new FileReader()
  const { files } = file.target
  if (files && files.length) {
    fileReader.readAsDataURL(files[0])
    fileReader.onload = () => {
      if (typeof fileReader.result === 'string')
        user.value.avatar = fileReader.result
    }
  }
}

const resetAvatar = () => {
  user.value.avatar = null
}

onMounted(fetchUser)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex">
          <VAvatar
            rounded
            size="100"
            class="me-6"
          >
            <VImg
              v-if="user?.avatar"
              :src="user.avatar"
            />
            <VIcon
              v-else
              icon="tabler-user"
              size="40"
            />
          </VAvatar>

          <form class="d-flex flex-column justify-center gap-4">
            <div class="d-flex flex-wrap gap-4">
              <VBtn
                color="primary"
                size="small"
                @click="refInputEl?.click()"
              >
                <VIcon
                  icon="tabler-cloud-upload"
                  class="d-sm-none"
                />
                <span class="d-none d-sm-block">Upload Photo</span>
              </VBtn>

              <input
                ref="refInputEl"
                type="file"
                name="file"
                accept=".jpeg,.png,.jpg,GIF"
                hidden
                @input="changeAvatar"
              >

              <VBtn
                type="reset"
                size="small"
                color="secondary"
                variant="tonal"
                @click="resetAvatar"
              >
                <span class="d-none d-sm-block">Reset</span>
                <VIcon
                  icon="tabler-refresh"
                  class="d-sm-none"
                />
              </VBtn>
            </div>

            <p class="text-body-1 mb-0">
              Allowed JPG, GIF or PNG. Max size of 800K
            </p>
          </form>
        </VCardText>

        <VCardText class="pt-2">
          <VForm
            ref="refForm"
            class="mt-3"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="accountDataLocal.firstName"
                  label="First Name"
                  placeholder="John"
                />
              </VCol>

              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="accountDataLocal.lastName"
                  label="Last Name"
                  placeholder="Doe"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.email"
                  label="E-mail"
                  placeholder="johndoe@example.com"
                  type="email"
                  disabled
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.phone"
                  label="Phone Number"
                  placeholder="+1 (917) 543-9876"
                />
              </VCol>

              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn type="submit">
                  Save Changes
                </VBtn>

                <VBtn
                  color="secondary"
                  variant="tonal"
                  type="reset"
                  @click.prevent="resetForm"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
