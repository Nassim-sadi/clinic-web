import axios from 'axios'

const BASE_URL = import.meta.env.VITE_API_URL || '/api'

const httpClient = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
})

httpClient.interceptors.request.use(config => {
  const token = useCookie('accessToken').value
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  
  return config
})

httpClient.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      useCookie('accessToken').value = null
      useCookie('userData').value = null
      window.location.href = '/login'
    }
    
    return Promise.reject(error)
  },
)

export const downloadFile = async (url, filename = 'download.pdf') => {
  const token = useCookie('accessToken').value

  const response = await fetch(`${BASE_URL}${url}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  })

  if (!response.ok)
    throw new Error('Download failed')

  const blob = await response.blob()
  const downloadUrl = window.URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = downloadUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(downloadUrl)
}

export const api = {
  get: (url, config) => httpClient.get(url, config),
  post: (url, data, config) => httpClient.post(url, data, config),
  put: (url, data, config) => httpClient.put(url, data, config),
  patch: (url, data, config) => httpClient.patch(url, data, config),
  delete: (url, config) => httpClient.delete(url, config),
}

export const auth = {
  login: credentials => api.post('/auth/login', credentials),
  register: data => api.post('/auth/register', data),
  logout: () => api.post('/auth/logout'),
  user: () => api.get('/auth/user'),
  updateProfile: data => api.put('/auth/profile', data),
}

export const clinics = {
  list: params => api.get('/clinics', { params }),
  show: id => api.get(`/clinics/${id}`),
  create: data => api.post('/clinics', data),
  update: (id, data) => api.put(`/clinics/${id}`, data),
  delete: id => api.delete(`/clinics/${id}`),
}

export const doctors = {
  list: params => api.get('/doctors', { params }),
  show: id => api.get(`/doctors/${id}`),
  create: data => api.post('/doctors', data),
  update: (id, data) => api.put(`/doctors/${id}`, data),
  delete: id => api.delete(`/doctors/${id}`),
}

export const patients = {
  list: params => api.get('/patients', { params }),
  show: id => api.get(`/patients/${id}`),
  create: data => api.post('/patients', data),
  update: (id, data) => api.put(`/patients/${id}`, data),
  delete: id => api.delete(`/patients/${id}`),
}

export const appointments = {
  list: params => api.get('/appointments', { params }),
  show: id => api.get(`/appointments/${id}`),
  create: data => api.post('/appointments', data),
  update: (id, data) => api.put(`/appointments/${id}`, data),
  delete: id => api.delete(`/appointments/${id}`),
  availableSlots: (doctorId, params) => api.get(`/appointments/${doctorId}/available-slots`, { params }),
}

export const services = {
  list: params => api.get('/services', { params }),
  show: id => api.get(`/services/${id}`),
  create: data => api.post('/services', data),
  update: (id, data) => api.put(`/services/${id}`, data),
  delete: id => api.delete(`/services/${id}`),
}

export const encounters = {
  list: params => api.get('/encounters', { params }),
  show: id => api.get(`/encounters/${id}`),
  create: data => api.post('/encounters', data),
  update: (id, data) => api.put(`/encounters/${id}`, data),
  delete: id => api.delete(`/encounters/${id}`),
  complete: (id, data) => api.post(`/encounters/${id}/complete`, data),
  byPatient: patientId => api.get(`/encounters/by-patient/${patientId}`),
  byAppointment: appointmentId => api.get(`/encounters/by-appointment/${appointmentId}`),
}

export const prescriptions = {
  list: params => api.get('/prescriptions', { params }),
  show: id => api.get(`/prescriptions/${id}`),
  create: data => api.post('/prescriptions', data),
  update: (id, data) => api.put(`/prescriptions/${id}`, data),
  delete: id => api.delete(`/prescriptions/${id}`),
  byPatient: patientId => api.get(`/prescriptions/by-patient/${patientId}`),
  byEncounter: encounterId => api.get(`/prescriptions/by-encounter/${encounterId}`),
  export: params => api.get('/prescriptions/export', { params }),
  download: id => downloadFile(`/prescriptions/${id}/download`),
}

export const bills = {
  list: params => api.get('/bills', { params }),
  show: id => api.get(`/bills/${id}`),
  create: data => api.post('/bills', data),
  update: (id, data) => api.put(`/bills/${id}`, data),
  delete: id => api.delete(`/bills/${id}`),
  pay: (id, data) => api.post(`/bills/${id}/pay`, data),
  byPatient: patientId => api.get(`/bills/by-patient/${patientId}`),
  byEncounter: encounterId => api.get(`/bills/by-encounter/${encounterId}`),
  download: id => downloadFile(`/bills/${id}/download`),
}

export const medicalHistories = {
  list: params => api.get('/medical-histories', { params }),
  show: id => api.get(`/medical-histories/${id}`),
  create: data => api.post('/medical-histories', data),
  update: (id, data) => api.put(`/medical-histories/${id}`, data),
  delete: id => api.delete(`/medical-histories/${id}`),
  byPatient: patientId => api.get(`/medical-histories/by-patient/${patientId}`),
}

export const waitingQueue = {
  list: params => api.get('/waiting-queue', { params }),
  show: id => api.get(`/waiting-queue/${id}`),
  create: data => api.post('/waiting-queue', data),
  update: (id, data) => api.put(`/waiting-queue/${id}`, data),
  delete: id => api.delete(`/waiting-queue/${id}`),
  stats: params => api.get('/waiting-queue/stats', { params }),
  display: params => api.get('/waiting-queue/display', { params }),
  reorder: items => api.post('/waiting-queue/reorder', { items }),
  call: id => api.post(`/waiting-queue/${id}/call`),
  start: id => api.post(`/waiting-queue/${id}/start`),
  complete: id => api.post(`/waiting-queue/${id}/complete`),
}

export const users = {
  list: params => api.get('/users', { params }),
  show: id => api.get(`/users/${id}`),
  create: data => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: id => api.delete(`/users/${id}`),
  assignRole: (id, role) => api.post(`/users/${id}/assign-role`, { role }),
  toggleStatus: id => api.post(`/users/${id}/toggle-status`),
}

export const roles = {
  list: () => api.get('/roles'),
}

export const permissions = {
  list: () => api.get('/permissions'),
}

export const dashboard = {
  stats: params => api.get('/dashboard/stats', { params }),
  recentAppointments: params => api.get('/dashboard/recent-appointments', { params }),
  appointmentStats: params => api.get('/dashboard/appointment-stats', { params }),
}

export const notifications = {
  list: params => api.get('/notifications', { params }),
  unreadCount: () => api.get('/notifications/unread-count'),
  markAsRead: id => api.post(`/notifications/${id}/mark-read`),
  markAllAsRead: () => api.post('/notifications/mark-all-read'),
  delete: id => api.delete(`/notifications/${id}`),
  clearAll: () => api.delete('/notifications/clear-all'),
}

export default api
