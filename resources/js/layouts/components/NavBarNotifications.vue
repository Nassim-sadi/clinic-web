<script setup>
import { useNotifications } from '@/composables/useNotifications'

const { notifications, unreadCount, markAsRead, markAllAsRead, formatTime } = useNotifications()

const show = ref(false)
</script>

<template>
  <VMenu
    v-model="show"
    :close-on-content-click="false"
    offset="12px"
    location="bottom end"
    width="350"
  >
    <template #activator="{ props }">
      <VBtn
        v-bind="props"
        icon
        variant="text"
      >
        <VBadge
          v-if="unreadCount > 0"
          :content="unreadCount"
          color="error"
        >
          <VIcon icon="tabler-bell" />
        </VBadge>
        <VIcon
          v-else
          icon="tabler-bell"
        />
      </VBtn>
    </template>

    <VCard>
      <VCardText class="d-flex align-center justify-space-between pa-3">
        <h5 class="text-body-1 font-weight-medium">
          Notifications
        </h5>
        <VBtn
          v-if="unreadCount > 0"
          variant="text"
          size="small"
          @click="markAllAsRead"
        >
          Mark all read
        </VBtn>
      </VCardText>

      <VDivider />

      <VList
        v-if="notifications.length"
        density="compact"
        class="py-0"
      >
        <template
          v-for="notification in notifications"
          :key="notification.id"
        >
          <VListItem
            :class="{ 'bg-surface': !notification.read_at }"
            @click="markAsRead(notification.id)"
          >
            <VListItemTitle class="text-body-2">
              {{ notification.title || notification.data?.title || 'New notification' }}
            </VListItemTitle>
            <VListItemSubtitle class="text-caption">
              {{ formatTime(notification.created_at) }}
            </VListItemSubtitle>
          </VListItem>
          <VDivider />
        </template>
      </VList>

      <VCardText
        v-else
        class="text-center text-disabled pa-4"
      >
        No notifications
      </VCardText>
    </VCard>
  </VMenu>
</template>
