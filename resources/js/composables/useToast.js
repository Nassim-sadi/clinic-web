import { ref, readonly } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
  const add = ({ message, type = 'success', timeout = 4000 }) => {
    const id = ++toastId

    toasts.value.push({ id, message, type, timeout })

    if (timeout > 0)
      setTimeout(() => remove(id), timeout)

    return id
  }

  const remove = id => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1)
      toasts.value.splice(index, 1)
  }

  const success = (message, timeout = 4000) => add({ message, type: 'success', timeout })
  const error = (message, timeout = 6000) => add({ message, type: 'error', timeout })
  const warning = (message, timeout = 5000) => add({ message, type: 'warning', timeout })
  const info = (message, timeout = 4000) => add({ message, type: 'info', timeout })

  return {
    toasts: readonly(toasts),
    add,
    remove,
    success,
    error,
    warning,
    info,
  }
}
