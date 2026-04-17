<script setup>
import { useTheme } from 'vuetify'
import { cookieRef, useLayoutConfigStore } from '@layouts/stores/config'
import { useConfigStore } from '@core/stores/config'

const configStore = useConfigStore()
const vuetifyTheme = useTheme()

const lightColors = [
  { name: 'Green', main: '#52F021', darken: '#48D81D' },
  { name: 'Teal', main: '#0D9394', darken: '#0C8485' },
  { name: 'Gold', main: '#FFB400', darken: '#E6A200' },
  { name: 'Red', main: '#FF4C51', darken: '#E64449' },
  { name: 'Blue', main: '#16B1FF', darken: '#149FE6' },
  { name: 'Purple', main: '#7367F0', darken: '#675DD8' },
]

const darkColors = [
  { name: 'Green', main: '#52F021', darken: '#48D81D' },
  { name: 'Teal', main: '#0D9394', darken: '#0C8485' },
  { name: 'Gold', main: '#FFB400', darken: '#E6A200' },
  { name: 'Red', main: '#FF4C51', darken: '#E64449' },
  { name: 'Blue', main: '#16B1FF', darken: '#149FE6' },
  { name: 'Purple', main: '#7367F0', darken: '#675DD8' },
]

const currentLightColor = computed(() => lightColors.find(c => c.main === vuetifyTheme.themes.value.light.colors.primary) || null)
const currentDarkColor = computed(() => darkColors.find(c => c.main === vuetifyTheme.themes.value.dark.colors.primary) || null)

const setLightColor = color => {
  vuetifyTheme.themes.value.light.colors.primary = color.main
  vuetifyTheme.themes.value.light.colors['primary-darken-1'] = color.darken
  cookieRef('lightThemePrimaryColor', null).value = color.main
  cookieRef('lightThemePrimaryDarkenColor', null).value = color.darken
}

const setDarkColor = color => {
  vuetifyTheme.themes.value.dark.colors.primary = color.main
  vuetifyTheme.themes.value.dark.colors['primary-darken-1'] = color.darken
  cookieRef('darkThemePrimaryColor', null).value = color.main
  cookieRef('darkThemePrimaryDarkenColor', null).value = color.darken
}

const themeOptions = [
  { name: 'light', icon: 'tabler-sun-high' },
  { name: 'dark', icon: 'tabler-moon-stars' },
  { name: 'system', icon: 'tabler-device-desktop-analytics' },
]
</script>

<template>
  <IconBtn id="theme-customizer-btn">
    <VIcon icon="tabler-palette" />

    <VTooltip activator="parent">
      Theme Settings
    </VTooltip>

    <VMenu
      activator="#theme-customizer-btn"
      location="bottom end"
      :offset="12"
      width="360"
    >
      <VCard>
        <VCardText class="pa-4">
          <div class="d-flex align-center justify-space-between mb-4">
            <h5 class="text-h5">
              Theme Settings
            </h5>
          </div>

          <VRow dense>
            <VCol cols="12">
              <h6 class="text-subtitle-2 mb-2">
                Theme Mode
              </h6>
              <VBtnToggle
                v-model="configStore.theme"
                mandatory
                density="compact"
                class="w-100"
              >
                <VBtn
                  v-for="option in themeOptions"
                  :key="option.name"
                  :value="option.name"
                  size="small"
                >
                  <VIcon
                    :icon="option.icon"
                    size="18"
                  />
                  <span class="ms-1 text-capitalize">{{ option.name }}</span>
                </VBtn>
              </VBtnToggle>
            </VCol>

            <VCol cols="12">
              <h6 class="text-subtitle-2 mb-2">
                Light Theme Colors
              </h6>
              <div class="d-flex flex-wrap gap-2">
                <div
                  v-for="color in lightColors"
                  :key="color.main"
                  class="color-swatch cursor-pointer rounded"
                  :class="currentLightColor?.main === color.main ? 'active' : ''"
                  :style="{ backgroundColor: color.main }"
                  @click="setLightColor(color)"
                >
                  <VIcon
                    v-if="currentLightColor?.main === color.main"
                    icon="tabler-check"
                    size="16"
                    class="text-white"
                  />
                </div>
              </div>
            </VCol>

            <VCol cols="12">
              <h6 class="text-subtitle-2 mb-2">
                Dark Theme Colors
              </h6>
              <div class="d-flex flex-wrap gap-2">
                <div
                  v-for="color in darkColors"
                  :key="color.main"
                  class="color-swatch cursor-pointer rounded"
                  :class="currentDarkColor?.main === color.main ? 'active' : ''"
                  :style="{ backgroundColor: color.main }"
                  @click="setDarkColor(color)"
                >
                  <VIcon
                    v-if="currentDarkColor?.main === color.main"
                    icon="tabler-check"
                    size="16"
                    class="text-white"
                  />
                </div>
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style scoped>
.color-swatch {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
}

.color-swatch:hover {
  transform: scale(1.1);
}

.color-swatch.active {
  outline: 2px solid rgb(var(--v-theme-on-surface));
  outline-offset: 2px;
}
</style>
