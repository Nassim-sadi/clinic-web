<script setup>
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const userData = computed(() => authStore.currentUser || useCookie('userData').value)

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <VMenu
    offset="12px"
    location="bottom end"
  >
    <template #activator="{ props }">
      <VBtn
        v-bind="props"
        icon
        variant="text"
      >
        <VAvatar
          size="38"
          color="primary"
          variant="tonal"
        >
          <VIcon icon="tabler-user" />
        </VAvatar>
      </VBtn>
    </template>

    <VCard min-width="200">
      <VCardText class="pa-3">
        <div class="d-flex align-center gap-3 mb-3">
          <VAvatar
            size="40"
            color="primary"
            variant="tonal"
          >
            <VIcon icon="tabler-user" />
          </VAvatar>
          <div>
            <h6 class="text-body-1 font-weight-medium">
              {{ userData?.name || 'User' }}
            </h6>
            <p class="text-caption text-disabled mb-0">
              {{ userData?.email || '' }}
            </p>
          </div>
        </div>

        <VDivider class="my-2" />

        <VList density="compact">
          <VListItem
            to="/settings"
            prepend-icon="tabler-settings"
          >
            <VListItemTitle>Settings</VListItemTitle>
          </VListItem>
        </VList>

        <VBtn
          block
          color="error"
          variant="tonal"
          size="small"
          prepend-icon="tabler-logout"
          class="mt-2"
          @click="logout"
        >
          Logout
        </VBtn>
      </VCardText>
    </VCard>
  </VMenu>
</template>
