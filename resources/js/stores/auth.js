import axios from 'axios'
import { defineStore } from 'pinia'
import { auth } from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: useCookie('userData').value || null,
    loading: false,
  }),

  getters: {
    currentUser: state => state.user,
    isLoggedIn: () => !!(useCookie('accessToken').value),
    userRole: state => state.user?.role || null,
    isAdmin: state => ['super_admin', 'clinic_admin', 'admin'].includes(state.user?.role),
    isDoctor: state => ['doctor', 'super_admin', 'clinic_admin', 'admin'].includes(state.user?.role),
    clinicId: state => state.user?.clinic_id || null,
  },

  actions: {
    async login(credentials) {
      this.loading = true
      try {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true })

        const response = await auth.login(credentials)
        const { user, token } = response.data

        useCookie('accessToken').value = token
        useCookie('userData').value = user
        this.user = user

        return response.data
      }
      catch (error) {
        throw error
      }
      finally {
        this.loading = false
      }
    },

    async register(data) {
      this.loading = true
      try {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true })

        const response = await auth.register(data)
        const { user, token } = response.data

        useCookie('accessToken').value = token
        useCookie('userData').value = user
        this.user = user

        return response.data
      }
      catch (error) {
        throw error
      }
      finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await auth.logout()
      }
      catch (e) {
        console.warn('Logout API failed:', e)
      }
      finally {
        this.clearAuth()
      }
    },

    async fetchUser() {
      if (!useCookie('accessToken').value)
        return

      try {
        const response = await auth.user()

        this.user = response.data
        useCookie('userData').value = response.data
      }
      catch (e) {
        this.clearAuth()
      }
    },

    clearAuth() {
      this.user = null
      useCookie('accessToken').value = null
      useCookie('userData').value = null
    },

    setUser(user) {
      this.user = user
      useCookie('userData').value = user
    },
  },
})

export default useAuthStore
