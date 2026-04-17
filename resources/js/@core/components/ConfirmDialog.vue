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
    max-width="450"
    persistent
    @click:outside="close"
  >
    <VCard class="confirm-dialog-card">
      <VCardText class="text-center pa-8">
        <VAvatar
          size="80"
          :color="confirmColor"
          variant="tonal"
          class="mb-5"
        >
          <VIcon
            size="40"
            :icon="icon"
          />
        </VAvatar>

        <h4 class="text-h4 font-weight-bold mb-3">
          {{ title }}
        </h4>

        <p class="text-body-1 text-medium-emphasis mb-6 px-2">
          {{ message }}
        </p>

        <div class="d-flex justify-center gap-4 pt-2">
          <VBtn
            variant="outlined"
            size="large"
            class="px-6"
            @click="close"
          >
            {{ cancelText }}
          </VBtn>
          <VBtn
            :color="confirmColor"
            size="large"
            class="px-6"
            @click="confirm"
          >
            {{ confirmText }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.confirm-dialog-card {
  border-radius: 16px;
}
</style>
