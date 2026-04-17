<script setup>
import { users, roles } from '@/services/api'
import UserModal from './UserModal.vue'
import ConfirmDialog from '@core/components/ConfirmDialog.vue'
import { useAuthStore } from '@/stores/auth'

definePage({
  meta: { title: 'User Management' },
})

const authStore = useAuthStore()
const currentUserId = computed(() => authStore.user?.id)

const search = ref('')
const usersList = ref([])
const loading = ref(false)
const showModal = ref(false)
const selectedUser = ref(null)
const rolesList = ref([])

const showConfirmDialog = ref(false)
const itemToDelete = ref(null)
const deleteLoading = ref(false)

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'roles.0.name' },
  { title: 'Status', key: 'is_active' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchUsers = async () => {
  loading.value = true
  try {
    const response = await users.list({ search: search.value })

    usersList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch users:', error)
  }
  finally {
    loading.value = false
  }
}

const fetchRoles = async () => {
  try {
    const response = await roles.list()

    rolesList.value = response.data.data || response.data
  }
  catch (error) {
    console.error('Failed to fetch roles:', error)
  }
}

const getStatusColor = isActive => {
  return isActive ? 'success' : 'error'
}

const getStatusText = isActive => {
  return isActive ? 'Active' : 'Inactive'
}

const openAddModal = () => {
  selectedUser.value = null
  showModal.value = true
}

const openEditModal = user => {
  selectedUser.value = user
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedUser.value = null
}

const handleSaved = () => {
  fetchUsers()
}

const toggleStatus = async user => {
  try {
    await users.toggleStatus(user.id)
    fetchUsers()
  }
  catch (error) {
    console.error('Failed to toggle status:', error)
  }
}

const confirmDelete = user => {
  itemToDelete.value = user
  showConfirmDialog.value = true
}

const deleteUser = async () => {
  if (!itemToDelete.value)
    return
  deleteLoading.value = true
  try {
    await users.delete(itemToDelete.value.id)
    fetchUsers()
  }
  catch (error) {
    console.error('Failed to delete user:', error)
  }
  finally {
    deleteLoading.value = false
    itemToDelete.value = null
  }
}

watch(search, fetchUsers, { debounce: 300 })
onMounted(() => {
  fetchUsers()
  fetchRoles()
})
</script>

<template>
  <VCard>
    <VCardText>
      <VRow>
        <VCol
          cols="12"
          md="6"
        >
          <AppTextField
            v-model="search"
            placeholder="Search users..."
            prepend-inner-icon="tabler-search"
            density="compact"
          />
        </VCol>
        <VCol
          cols="12"
          md="6"
          class="text-right"
        >
          <VBtn
            color="primary"
            @click="openAddModal"
          >
            <VIcon
              icon="tabler-plus"
              class="me-2"
            />
            Add User
          </VBtn>
        </VCol>
      </VRow>
    </VCardText>

    <VTable>
      <thead>
        <tr>
          <th
            v-for="header in headers"
            :key="header.key"
          >
            {{ header.title }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="user in usersList"
          :key="user.id"
        >
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td>
            <VChip
              size="small"
              variant="tonal"
            >
              {{ user.roles?.[0]?.name || 'No role' }}
            </VChip>
          </td>
          <td>
            <VChip
              :color="getStatusColor(user.is_active)"
              size="small"
            >
              {{ getStatusText(user.is_active) }}
            </VChip>
          </td>
          <td>{{ user.created_at }}</td>
          <td>
            <IconBtn @click="openEditModal(user)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              :disabled="user.id === currentUserId"
              @click="toggleStatus(user)"
            >
              <VIcon icon="tabler-power" />
            </IconBtn>
            <IconBtn
              color="error"
              :disabled="user.id === currentUserId"
              @click="confirmDelete(user)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </td>
        </tr>
        <tr v-if="usersList.length === 0 && !loading">
          <td
            colspan="6"
            class="text-center text-disabled pa-4"
          >
            No users found
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>

  <Teleport to="body">
    <UserModal
      v-if="showModal"
      :user="selectedUser"
      @saved="handleSaved"
      @closed="closeModal"
    />

    <ConfirmDialog
      v-model="showConfirmDialog"
      title="Delete User"
      :message="`Are you sure you want to delete ${itemToDelete?.name}?`"
      confirm-text="Delete"
      :loading="deleteLoading"
      @confirm="deleteUser"
    />
  </Teleport>

  <VOverlay
    v-model="loading"
    contained
    class="align-center justify-center"
  >
    <VProgressCircular indeterminate />
  </VOverlay>
</template>
