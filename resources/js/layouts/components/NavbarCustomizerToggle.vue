<script setup>
const customizerRef = ref(null)

const openCustomizer = () => {
  if (customizerRef.value) {
    customizerRef.value.isNavDrawerOpen = true
  }
}
</script>

<template>
  <IconBtn @click="openCustomizer">
    <VIcon icon="tabler-settings" />

    <VTooltip activator="parent">
      Customize Theme
    </VTooltip>
  </IconBtn>

  <TheCustomizer ref="customizerRef" />
</template>
