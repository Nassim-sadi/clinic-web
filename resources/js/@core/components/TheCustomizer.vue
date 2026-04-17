<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useTheme } from 'vuetify'
import {
  staticPrimaryColor,
  staticPrimaryDarkenColor,
} from '@/plugins/vuetify/theme'
import {
  Direction,
  Layout,
  Skins,
  Theme,
} from '@core/enums'
import { useConfigStore } from '@core/stores/config'
import {
  AppContentLayoutNav,
  ContentWidth,
} from '@layouts/enums'
import {
  cookieRef,
  namespaceConfig,
} from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'

defineExpose({ open })

const isOpen = ref(false)

const open = () => {
  isOpen.value = true
}

const configStore = useConfigStore()
const vuetifyTheme = useTheme()

const primaryColors = [
  { main: staticPrimaryColor, darken: staticPrimaryDarkenColor },
  { main: '#0D9394', darken: '#0C8485' },
  { main: '#FFB400', darken: '#E6A200' },
  { main: '#FF4C51', darken: '#E64449' },
  { main: '#16B1FF', darken: '#149FE6' },
  { main: '#7367F0', darken: '#675DD8' },
]

const lightBackgroundColors = [
  { name: 'White', bg: '#FFFFFF', surface: '#FFFFFF' },
  { name: 'Light Gray', bg: '#F8F7FA', surface: '#FFFFFF' },
  { name: 'Light Blue', bg: '#F0F4F8', surface: '#FFFFFF' },
  { name: 'Light Green', bg: '#F0F8F0', surface: '#FFFFFF' },
  { name: 'Light Purple', bg: '#F5F0FF', surface: '#FFFFFF' },
  { name: 'Light Pink', bg: '#FFF0F5', surface: '#FFFFFF' },
]

const darkBackgroundColors = [
  { name: 'Dark Blue', bg: '#1A1A2E', surface: '#252640' },
  { name: 'Dark Gray', bg: '#1E1E2D', surface: '#2D2D44' },
  { name: 'Dark Teal', bg: '#1A2E2E', surface: '#254545' },
  { name: 'Dark Purple', bg: '#1E1A2E', surface: '#2D2545' },
  { name: 'Dark Navy', bg: '#0D1B2A', surface: '#1B2838' },
  { name: 'Dark Slate', bg: '#1A1D23', surface: '#252A33' },
]

const customPrimaryColor = ref('#663131')

watch(() => configStore.theme, () => {
  const cookiePrimaryColor = cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryColor`, null).value
  if (cookiePrimaryColor && !primaryColors.some(color => color.main === cookiePrimaryColor))
    customPrimaryColor.value = cookiePrimaryColor
}, { immediate: true })

const setPrimaryColor = color => {
  vuetifyTheme.themes.value[vuetifyTheme.name.value].colors.primary = color.main
  vuetifyTheme.themes.value[vuetifyTheme.name.value].colors['primary-darken-1'] = color.darken
  cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryColor`, null).value = color.main
  cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryDarkenColor`, null).value = color.darken
  useStorage(namespaceConfig('initial-loader-color'), null).value = color.main
}

const setLightBackground = bg => {
  vuetifyTheme.themes.value.light.colors.background = bg.bg
  vuetifyTheme.themes.value.light.colors.surface = bg.surface
  cookieRef('lightThemeBackground', null).value = bg.bg
  cookieRef('lightThemeSurface', null).value = bg.surface
}

const setDarkBackground = bg => {
  vuetifyTheme.themes.value.dark.colors.background = bg.bg
  vuetifyTheme.themes.value.dark.colors.surface = bg.surface
  cookieRef('darkThemeBackground', null).value = bg.bg
  cookieRef('darkThemeSurface', null).value = bg.surface
}

const currentLightBg = computed(() => lightBackgroundColors.find(c => c.bg === vuetifyTheme.themes.value.light.colors.background) || null)
const currentDarkBg = computed(() => darkBackgroundColors.find(c => c.bg === vuetifyTheme.themes.value.dark.colors.background) || null)

const themeMode = [
  { bgImage: 'tabler-sun', value: Theme.Light, label: 'Light' },
  { bgImage: 'tabler-moon-stars', value: Theme.Dark, label: 'Dark' },
  { bgImage: 'tabler-device-desktop-analytics', value: Theme.System, label: 'System' },
]

const themeSkin = [
  { bgImage: 'default', value: Skins.Default, label: 'Default' },
  { bgImage: 'bordered', value: Skins.Bordered, label: 'Bordered' },
]

const currentLayout = ref(configStore.isVerticalNavCollapsed ? 'collapsed' : configStore.appContentLayoutNav)

const layouts = [
  { bgImage: 'vertical', value: Layout.Vertical, label: 'Vertical' },
  { bgImage: 'collapsed', value: Layout.Collapsed, label: 'Collapsed' },
  { bgImage: 'horizontal', value: Layout.Horizontal, label: 'Horizontal' },
]

watch(currentLayout, () => {
  if (currentLayout.value === 'collapsed') {
    configStore.isVerticalNavCollapsed = true
    configStore.appContentLayoutNav = AppContentLayoutNav.Vertical
  }
  else {
    configStore.isVerticalNavCollapsed = false
    configStore.appContentLayoutNav = currentLayout.value
  }
})

watch(() => configStore.isVerticalNavCollapsed, () => {
  currentLayout.value = configStore.isVerticalNavCollapsed ? 'collapsed' : configStore.appContentLayoutNav
})

const contentWidth = [
  { value: ContentWidth.Boxed, label: 'Compact' },
  { value: ContentWidth.Fluid, label: 'Wide' },
]

const currentDir = ref(configStore.isAppRTL ? 'rtl' : 'ltr')

const direction = [
  { value: Direction.Ltr, label: 'Left to right' },
  { value: Direction.Rtl, label: 'Right to left' },
]

watch(currentDir, () => {
  if (currentDir.value === 'rtl')
    configStore.isAppRTL = true
  else
    configStore.isAppRTL = false
})

const isCookieHasAnyValue = ref(false)
const { locale } = useI18n({ useScope: 'global' })

const isActiveLangRTL = computed(() => {
  const lang = themeConfig.app.i18n.langConfig.find(l => l.i18nLang === locale.value)
  
  return lang?.isRTL ?? false
})

watch([
  () => vuetifyTheme.current.value.colors.primary,
  configStore.$state,
  locale,
], () => {
  const initialConfigValue = [
    staticPrimaryColor,
    staticPrimaryColor,
    themeConfig.app.theme,
    themeConfig.app.skin,
    themeConfig.verticalNav.isVerticalNavSemiDark,
    themeConfig.verticalNav.isVerticalNavCollapsed,
    themeConfig.app.contentWidth,
    isActiveLangRTL.value,
    themeConfig.app.contentLayoutNav,
  ]

  const themeConfigValue = [
    vuetifyTheme.themes.value.light.colors.primary,
    vuetifyTheme.themes.value.dark.colors.primary,
    configStore.theme,
    configStore.skin,
    configStore.isVerticalNavSemiDark,
    configStore.isVerticalNavCollapsed,
    configStore.appContentWidth,
    configStore.isAppRTL,
    configStore.appContentLayoutNav,
  ]

  currentDir.value = configStore.isAppRTL ? 'rtl' : 'ltr'
  isCookieHasAnyValue.value = JSON.stringify(themeConfigValue) !== JSON.stringify(initialConfigValue)
}, {
  deep: true,
  immediate: true,
})

const resetCustomizer = async () => {
  if (isCookieHasAnyValue.value) {
    vuetifyTheme.themes.value.light.colors.primary = staticPrimaryColor
    vuetifyTheme.themes.value.dark.colors.primary = staticPrimaryColor
    vuetifyTheme.themes.value.light.colors['primary-darken-1'] = staticPrimaryDarkenColor
    vuetifyTheme.themes.value.dark.colors['primary-darken-1'] = staticPrimaryDarkenColor
    configStore.theme = themeConfig.app.theme
    configStore.skin = themeConfig.app.skin
    configStore.isVerticalNavSemiDark = themeConfig.verticalNav.isVerticalNavSemiDark
    configStore.appContentLayoutNav = themeConfig.app.contentLayoutNav
    configStore.appContentWidth = themeConfig.app.contentWidth
    configStore.isAppRTL = isActiveLangRTL.value
    configStore.isVerticalNavCollapsed = themeConfig.verticalNav.isVerticalNavCollapsed
    useStorage(namespaceConfig('initial-loader-color'), null).value = staticPrimaryColor
    currentLayout.value = themeConfig.app.contentLayoutNav
    cookieRef('lightThemePrimaryColor', null).value = null
    cookieRef('darkThemePrimaryColor', null).value = null
    cookieRef('lightThemePrimaryDarkenColor', null).value = null
    cookieRef('darkThemePrimaryDarkenColor', null).value = null
    cookieRef('lightThemeBackground', null).value = null
    cookieRef('darkThemeBackground', null).value = null
    cookieRef('lightThemeSurface', null).value = null
    cookieRef('darkThemeSurface', null).value = null
    await nextTick()
    isCookieHasAnyValue.value = false
    customPrimaryColor.value = '#ffffff'
  }
}
</script>

<template>
  <VDialog
    v-model="isOpen"
    location="end"
    max-width="420"
    scrollable
  >
    <VCard class="theme-customizer">
      <div class="customizer-heading d-flex align-center justify-space-between">
        <div>
          <h6 class="text-h6">
            Theme Customizer
          </h6>
          <p class="text-body-2 mb-0">
            Customize & Preview in Real Time
          </p>
        </div>

        <div class="d-flex align-center gap-1">
          <VBtn
            icon
            variant="text"
            size="small"
            color="medium-emphasis"
            @click="resetCustomizer"
          >
            <VBadge
              v-show="isCookieHasAnyValue"
              dot
              color="error"
              offset-x="-29"
              offset-y="-14"
            />

            <VIcon
              size="24"
              color="high-emphasis"
              icon="tabler-refresh"
            />
          </VBtn>

          <VBtn
            icon
            variant="text"
            color="medium-emphasis"
            size="small"
            @click="isOpen = false"
          >
            <VIcon
              icon="tabler-x"
              color="high-emphasis"
              size="24"
            />
          </VBtn>
        </div>
      </div>

      <VDivider />

      <PerfectScrollbar
        tag="div"
        :options="{ wheelPropagation: false }"
        class="customizer-content"
      >
        <VCardText class="pa-4">
          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Primary Color
          </h6>
          <div class="d-flex flex-wrap gap-2 mb-4">
            <div
              v-for="color in primaryColors"
              :key="color.main"
              class="color-swatch cursor-pointer"
              :class="vuetifyTheme.current.value.colors.primary === color.main ? 'active' : ''"
              :style="{ backgroundColor: color.main }"
              @click="setPrimaryColor(color)"
            >
              <VIcon
                v-if="vuetifyTheme.current.value.colors.primary === color.main"
                icon="tabler-check"
                size="16"
                class="text-white"
              />
            </div>
          </div>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Light Background
          </h6>
          <div class="d-flex flex-wrap gap-2 mb-4">
            <div
              v-for="bg in lightBackgroundColors"
              :key="bg.bg"
              class="bg-swatch cursor-pointer"
              :class="currentLightBg?.bg === bg.bg ? 'active' : ''"
              :style="{ backgroundColor: bg.bg }"
              @click="setLightBackground(bg)"
            >
              <VIcon
                v-if="currentLightBg?.bg === bg.bg"
                icon="tabler-check"
                size="16"
                class="text-white"
              />
            </div>
          </div>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Dark Background
          </h6>
          <div class="d-flex flex-wrap gap-2 mb-4">
            <div
              v-for="bg in darkBackgroundColors"
              :key="bg.bg"
              class="bg-swatch cursor-pointer"
              :class="currentDarkBg?.bg === bg.bg ? 'active' : ''"
              :style="{ backgroundColor: bg.bg }"
              @click="setDarkBackground(bg)"
            >
              <VIcon
                v-if="currentDarkBg?.bg === bg.bg"
                icon="tabler-check"
                size="16"
                class="text-white"
              />
            </div>
          </div>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Theme
          </h6>
          <VBtnToggle
            v-model="configStore.theme"
            mandatory
            density="compact"
            class="mb-4"
          >
            <VBtn
              v-for="option in themeMode"
              :key="option.value"
              :value="option.value"
              size="small"
            >
              <VIcon
                :icon="option.bgImage"
                size="18"
              />
              <span class="ms-1">{{ option.label }}</span>
            </VBtn>
          </VBtnToggle>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Skins
          </h6>
          <VBtnToggle
            v-model="configStore.skin"
            mandatory
            density="compact"
            class="mb-4"
          >
            <VBtn
              v-for="option in themeSkin"
              :key="option.value"
              :value="option.value"
              size="small"
            >
              {{ option.label }}
            </VBtn>
          </VBtnToggle>

          <div
            v-if="vuetifyTheme.global.name.value === 'light' && configStore.appContentLayoutNav === AppContentLayoutNav.Vertical"
            class="d-flex align-center justify-space-between mb-4"
          >
            <span class="text-subtitle-1 font-weight-medium">
              Semi Dark Menu
            </span>
            <VSwitch
              v-model="configStore.isVerticalNavSemiDark"
              class="ms-2"
              hide-details
            />
          </div>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Layout
          </h6>
          <VBtnToggle
            v-model="currentLayout"
            density="compact"
            class="mb-4"
          >
            <VBtn
              v-for="option in layouts"
              :key="option.value"
              :value="option.value"
              size="small"
            >
              {{ option.label }}
            </VBtn>
          </VBtnToggle>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Content
          </h6>
          <VBtnToggle
            v-model="configStore.appContentWidth"
            density="compact"
            class="mb-4"
          >
            <VBtn
              v-for="option in contentWidth"
              :key="option.value"
              :value="option.value"
              size="small"
            >
              {{ option.label }}
            </VBtn>
          </VBtnToggle>

          <h6 class="text-subtitle-1 font-weight-medium mb-3">
            Direction
          </h6>
          <VBtnToggle
            v-model="currentDir"
            density="compact"
          >
            <VBtn
              v-for="option in direction"
              :key="option.value"
              :value="option.value"
              size="small"
            >
              {{ option.label }}
            </VBtn>
          </VBtnToggle>
        </VCardText>
      </PerfectScrollbar>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.theme-customizer {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.customizer-heading {
  padding: 1rem 1.5rem;
  flex-shrink: 0;
}

.customizer-content {
  flex: 1;
  overflow-y: auto;
}

.color-swatch {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  border: 2px solid transparent;

  &:hover {
    transform: scale(1.1);
  }

  &.active {
    border-color: rgb(var(--v-theme-on-surface));
  }
}

.bg-swatch {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  border: 2px solid rgba(var(--v-border-color), var(--v-border-opacity));

  &:hover {
    transform: scale(1.1);
  }

  &.active {
    border-color: rgb(var(--v-theme-primary));
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
  }
}
</style>
