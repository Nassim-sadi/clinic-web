<script setup>
import { useToast } from '@core/composables/useToast'
import { activityLogs } from '@services/api'

const { showToast } = useToast()

const isCurrentPasswordVisible = ref(false)
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const isSaving = ref(false)

const passwordRequirements = [
  'Minimum 8 characters long - the more, the better',
  'At least one lowercase character',
  'At least one number, symbol, or whitespace character',
]

const activityHeaders = [
  {
    title: 'DESCRIPTION',
    key: 'description',
  },
  {
    title: 'TYPE',
    key: 'log_name',
  },
  {
    title: 'DATE & TIME',
    key: 'created_at',
  },
]

const activityLogsList = ref([])
const activityLogsLoading = ref(false)
const activityLogNames = ref([])
const selectedLogName = ref(null)
const activitySearch = ref('')
const activityTotal = ref(0)

const fetchActivityLogs = async () => {
  activityLogsLoading.value = true
  try {
    const { data } = await activityLogs.list({
      log_name: selectedLogName.value || undefined,
      search: activitySearch.value || undefined,
    })
    activityLogsList.value = data.data || []
    activityTotal.value = data.total || 0
  } catch (err) {
    showToast('Failed to load activity logs', { type: 'error' })
  } finally {
    activityLogsLoading.value = false
  }
}

const fetchLogNames = async () => {
  try {
    const { data } = await activityLogs.logNames()
    activityLogNames.value = data || []
  } catch (err) {
    console.error('Failed to load log names', err)
  }
}

watch([selectedLogName, activitySearch], useDebounceFn(() => {
  fetchActivityLogs()
}, 500))

onMounted(() => {
  fetchActivityLogs()
  fetchLogNames()
})

const changePassword = async () => {
  if (newPassword.value !== confirmPassword.value) {
    showToast('Passwords do not match', { type: 'error' })
    return
  }

  if (newPassword.value.length < 8) {
    showToast('Password must be at least 8 characters', { type: 'error' })
    return
  }

  isSaving.value = true
  try {
    await api.put('/auth/change-password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    showToast('Password changed successfully', { type: 'success' })
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to change password'
    showToast(message, { type: 'error' })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <VRow>
    <!-- SECTION: Change Password -->
    <VCol cols="12">
      <VCard title="Change Password">
        <VForm @submit.prevent="changePassword">
          <VCardText class="pt-0">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="currentPassword"
                  :type="isCurrentPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCurrentPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Current Password"
                  autocomplete="on"
                  placeholder="············"
                  @click:append-inner="isCurrentPasswordVisible = !isCurrentPasswordVisible"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="newPassword"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="New Password"
                  autocomplete="on"
                  placeholder="············"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="confirmPassword"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Confirm New Password"
                  autocomplete="on"
                  placeholder="············"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>
            </VRow>
          </VCardText>

          <VCardText>
            <h6 class="text-h6 text-medium-emphasis mb-4">
              Password Requirements:
            </h6>

            <VList class="card-list">
              <VListItem
                v-for="item in passwordRequirements"
                :key="item"
                :title="item"
                class="text-medium-emphasis"
              >
                <template #prepend>
                  <VIcon
                    size="10"
                    icon="tabler-circle-filled"
                  />
                </template>
              </VListItem>
            </VList>
          </VCardText>

          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn
              type="submit"
              :loading="isSaving"
            >
              Save changes
            </VBtn>

            <VBtn
              type="reset"
              color="secondary"
              variant="tonal"
            >
              Reset
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Email Verification -->
    <VCol cols="12">
      <VCard title="Email Verification">
        <VCardText>
          <div class="d-flex align-center gap-3 mb-4">
            <VAvatar
              color="success"
              variant="tonal"
              size="42"
            >
              <VIcon icon="tabler-mail" />
            </VAvatar>
            <div>
              <h5 class="text-h5">
                Email verification is active
              </h5>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Your email address is verified and secure.
              </p>
            </div>
          </div>
          <p class="text-body-2 text-medium-emphasis">
            Email verification ensures that only you can access your account
            through secure authentication links sent to your registered email.
          </p>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Activity Log -->
    <VCol cols="12">
      <VCard title="Activity Log">
        <VCardText>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Track all changes made to your account including profile updates,
            role changes, and other security-related activities.
          </p>

          <VRow class="mb-4">
            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="activitySearch"
                prepend-inner-icon="tabler-search"
                placeholder="Search activities..."
                hide-details
                density="compact"
              />
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <AppSelect
                v-model="selectedLogName"
                :items="[{ title: 'All Types', value: null }, ...activityLogNames.map(n => ({ title: n, value: n }))]"
                placeholder="Filter by type"
                hide-details
                density="compact"
                clearable
              />
            </VCol>
          </VRow>

          <VDataTable
            :headers="activityHeaders"
            :items="activityLogsList"
            :loading="activityLogsLoading"
            :items-length="activityTotal"
            hide-default-footer
            class="text-no-wrap"
          >
            <template #item.description="{ item }">
              <div class="text-body-2">
                {{ item.description }}
              </div>
              <div
                v-if="item.properties && Object.keys(item.properties).length > 0"
                class="text-caption text-medium-emphasis"
              >
                <span
                  v-for="(value, key) in item.properties"
                  :key="key"
                >
                  {{ key }}: {{ typeof value === 'object' ? JSON.stringify(value) : value }}
                </span>
              </div>
            </template>
            <template #item.log_name="{ item }">
              <VChip
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ item.log_name || 'default' }}
              </VChip>
            </template>
            <template #item.created_at="{ item }">
              {{ new Date(item.created_at).toLocaleString() }}
            </template>
            <template #bottom />
          </VDataTable>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->
  </VRow>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}
</style>
