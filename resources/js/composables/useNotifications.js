import { ref } from 'vue'
import { notifications } from '@/services/api'

const POLL_INTERVAL = 30000

const notificationList = ref([])
const unreadCount = ref(0)
const isLoading = ref(false)
let pollTimer = null
let lastNotificationId = null

export function useNotifications() {
  const fetchNotifications = async () => {
    if (!useCookie('accessToken').value)
      return

    isLoading.value = true
    try {
      const response = await notifications.list({ limit: 20 })

      notificationList.value = response.data

      const countResponse = await notifications.unreadCount()

      unreadCount.value = countResponse.data.count

      if (notificationList.value.length > 0 && lastNotificationId !== notificationList.value[0]?.id) {
        if (lastNotificationId !== null) {
          const newNotifications = notificationList.value.filter(n => n.id !== lastNotificationId)
          if (newNotifications.length > 0) {
            window.dispatchEvent(new CustomEvent('new-notification', {
              detail: { notifications: newNotifications },
            }))
          }
        }
        lastNotificationId = notificationList.value[0]?.id
      }
    }
    catch (error) {
      console.error('Failed to fetch notifications:', error)
    }
    finally {
      isLoading.value = false
    }
  }

  const markAsRead = async id => {
    try {
      await notifications.markAsRead(id)

      const notification = notificationList.value.find(n => n.id === id)
      if (notification) {
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    }
    catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  const markAllAsRead = async () => {
    try {
      await notifications.markAllAsRead()
      notificationList.value.forEach(n => n.read_at = new Date().toISOString())
      unreadCount.value = 0
    }
    catch (error) {
      console.error('Failed to mark all as read:', error)
    }
  }

  const deleteNotification = async id => {
    try {
      await notifications.delete(id)

      const index = notificationList.value.findIndex(n => n.id === id)
      if (index !== -1) {
        if (!notificationList.value[index].read_at)
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        notificationList.value.splice(index, 1)
      }
    }
    catch (error) {
      console.error('Failed to delete notification:', error)
    }
  }

  const clearAll = async () => {
    try {
      await notifications.clearAll()
      notificationList.value = []
      unreadCount.value = 0
    }
    catch (error) {
      console.error('Failed to clear notifications:', error)
    }
  }

  const startPolling = () => {
    stopPolling()
    fetchNotifications()
    pollTimer = setInterval(fetchNotifications, POLL_INTERVAL)
  }

  const stopPolling = () => {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  const getNotificationIcon = type => {
    const icons = {
      patient_created: 'tabler-user-plus',
      appointment_created: 'tabler-calendar-plus',
      appointment_updated: 'tabler-calendar-edit',
      appointment_cancelled: 'tabler-calendar-x',
      doctor_status_changed: 'tabler-stethoscope',
      encounter_created: 'tabler-notes',
      bill_created: 'tabler-receipt',
      bill_paid: 'tabler-cash',
    }

    
    return icons[type] || 'tabler-bell'
  }

  const getNotificationColor = type => {
    const colors = {
      patient_created: 'success',
      appointment_created: 'info',
      appointment_updated: 'warning',
      appointment_cancelled: 'error',
      doctor_status_changed: 'purple',
      encounter_created: 'primary',
      bill_created: 'info',
      bill_paid: 'success',
    }

    
    return colors[type] || 'grey'
  }

  const formatTime = dateString => {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now - date
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1)
      return 'Just now'
    if (diffMins < 60)
      return `${diffMins}m ago`
    if (diffHours < 24)
      return `${diffHours}h ago`
    if (diffDays < 7)
      return `${diffDays}d ago`

    return date.toLocaleDateString()
  }

  return {
    notifications: notificationList,
    unreadCount,
    isLoading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearAll,
    startPolling,
    stopPolling,
    getNotificationIcon,
    getNotificationColor,
    formatTime,
  }
}
