<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirm' },
  message: { type: String, default: 'Are you sure?' },
  confirmText: { type: String, default: 'Delete' },
  cancelText: { type: String, default: 'Cancel' },
  confirmColor: { type: String, default: 'error' },
  icon: { type: String, default: 'tabler-trash' },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const close = () => {
  emit('update:modelValue', false)
  emit('cancel')
}

const confirm = () => {
  emit('confirm')
  close()
}
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="400"
    persistent
    @click:outside="close"
  >
    <VCard>
      <VCardText class="text-center pa-6">
        <VAvatar
          size="72"
          color="error"
          variant="tonal"
          class="mb-4"
        >
          <VIcon
            size="36"
            :icon="icon"
          />
        </VAvatar>

        <h5 class="text-h5 mb-2">
          {{ title }}
        </h5>

        <p class="text-body-1 text-medium-emphasis mb-6">
          {{ message }}
        </p>

        <div class="d-flex justify-center gap-3">
          <VBtn
            variant="outlined"
            @click="close"
          >
            {{ cancelText }}
          </VBtn>
          <VBtn
            :color="confirmColor"
            @click="confirm"
          >
            {{ confirmText }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
