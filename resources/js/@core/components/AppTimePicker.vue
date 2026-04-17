<script setup>
const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  errorMessages: { type: [String, Array], default: () => [] },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const menu = ref(false)
const hours = ref('')
const minutes = ref('')

const hourOptions = computed(() => {
  return Array.from({ length: 24 }, (_, i) => ({
    title: i.toString().padStart(2, '0'),
    value: i,
  }))
})

const minuteOptions = computed(() => {
  return Array.from({ length: 12 }, (_, i) => ({
    title: (i * 5).toString().padStart(2, '0'),
    value: i * 5,
  }))
})

const open = () => {
  if (props.modelValue) {
    const [h, m] = props.modelValue.split(':')

    hours.value = parseInt(h) || 0
    minutes.value = Math.round((parseInt(m) || 0) / 5) * 5
  }
  else {
    hours.value = new Date().getHours()
    minutes.value = Math.round(new Date().getMinutes() / 5) * 5
  }
  menu.value = true
}

const confirm = () => {
  const time = `${hours.value.toString().padStart(2, '0')}:${minutes.value.toString().padStart(2, '0')}`

  emit('update:modelValue', time)
  menu.value = false
}

const clear = () => {
  emit('update:modelValue', '')
  menu.value = false
}

const displayTime = computed(() => {
  if (!props.modelValue)
    return ''
  const [h, m] = props.modelValue.split(':')
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  
  return `${displayHour}:${m} ${ampm}`
})
</script>

<template>
  <VMenu
    v-model="menu"
    location="bottom"
    :close-on-content-click="false"
  >
    <template #activator="{ props: menuProps }">
      <AppTextField
        :model-value="displayTime"
        v-bind="menuProps"
        :label="label"
        :error-messages="errorMessages"
        :disabled="disabled"
        :readonly="readonly"
        placeholder="Select time"
        @click="open"
      >
        <template #append-inner>
          <VIcon
            icon="tabler-clock"
            size="20"
            class="cursor-pointer"
            @click="open"
          />
        </template>
      </AppTextField>
    </template>

    <VCard class="time-picker-card pa-4">
      <div class="d-flex align-center justify-center gap-4">
        <div class="text-center">
          <h6 class="text-caption text-medium-emphasis mb-2">
            HOUR
          </h6>
          <VSelect
            v-model="hours"
            :items="hourOptions"
            variant="outlined"
            density="compact"
            hide-details
            class="time-select"
            scrollable
          />
        </div>

        <span class="text-h4 font-weight-bold mt-n4">:</span>

        <div class="text-center">
          <h6 class="text-caption text-medium-emphasis mb-2">
            MIN
          </h6>
          <VSelect
            v-model="minutes"
            :items="minuteOptions"
            variant="outlined"
            density="compact"
            hide-details
            class="time-select"
            scrollable
          />
        </div>
      </div>

      <div class="d-flex justify-end gap-2 mt-4">
        <VBtn
          variant="text"
          size="small"
          @click="clear"
        >
          Clear
        </VBtn>
        <VBtn
          variant="text"
          size="small"
          @click="menu = false"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          size="small"
          @click="confirm"
        >
          OK
        </VBtn>
      </div>
    </VCard>
  </VMenu>
</template>

<style scoped>
.time-picker-card {
  min-width: 280px;
}

.time-select {
  width: 90px;
}
</style>
