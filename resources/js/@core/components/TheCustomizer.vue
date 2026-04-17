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
import horizontalLight from '@images/customizer-icons/horizontal-light.svg'
import {
  AppContentLayoutNav,
  ContentWidth,
} from '@layouts/enums'
import {
  cookieRef,
  namespaceConfig,
} from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'
import borderSkin from '@images/customizer-icons/border-light.svg'
import collapsed from '@images/customizer-icons/collapsed-light.svg'
import compact from '@images/customizer-icons/compact-light.svg'
import defaultSkin from '@images/customizer-icons/default-light.svg'
import ltrSvg from '@images/customizer-icons/ltr-light.svg'
import rtlSvg from '@images/customizer-icons/rtl-light.svg'
import wideSvg from '@images/customizer-icons/wide-light.svg'

const isNavDrawerOpen = ref(false)

defineExpose({ isNavDrawerOpen })

const configStore = useConfigStore()

const vuetifyTheme = useTheme()

const primaryColors = [
  {
    main: staticPrimaryColor,
    darken: staticPrimaryDarkenColor,
  },
  {
    main: '#0D9394',
    darken: '#0C8485',
  },
  {
    main: '#FFB400',
    darken: '#E6A200',
  },
  {
    main: '#FF4C51',
    darken: '#E64449',
  },
  {
    main: '#16B1FF',
    darken: '#149FE6',
  },
  {
    main: '#7367F0',
    darken: '#675DD8',
  },
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

const setPrimaryColor = useDebounceFn(color => {
  vuetifyTheme.themes.value[vuetifyTheme.name.value].colors.primary = color.main
  vuetifyTheme.themes.value[vuetifyTheme.name.value].colors['primary-darken-1'] = color.darken

  cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryColor`, null).value = color.main
  cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryDarkenColor`, null).value = color.darken

  useStorage(namespaceConfig('initial-loader-color'), null).value = color.main
}, 100)

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

const themeMode = computed(() => [
  {
    bgImage: 'tabler-sun',
    value: Theme.Light,
    label: 'Light',
  },
  {
    bgImage: 'tabler-moon-stars',
    value: Theme.Dark,
    label: 'Dark',
  },
  {
    bgImage: 'tabler-device-desktop-analytics',
    value: Theme.System,
    label: 'System',
  },
])

const themeSkin = computed(() => [
  {
    bgImage: defaultSkin,
    value: Skins.Default,
    label: 'Default',
  },
  {
    bgImage: borderSkin,
    value: Skins.Bordered,
    label: 'Bordered',
  },
])

const currentLayout = ref(configStore.isVerticalNavCollapsed ? 'collapsed' : configStore.appContentLayoutNav)

const layouts = computed(() => [
  {
    bgImage: defaultSkin,
    value: Layout.Vertical,
    label: 'Vertical',
  },
  {
    bgImage: collapsed,
    value: Layout.Collapsed,
    label: 'Collapsed',
  },
  {
    bgImage: horizontalLight,
    value: Layout.Horizontal,
    label: 'Horizontal',
  },
])

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

const contentWidth = computed(() => [
  {
    bgImage: compact,
    value: ContentWidth.Boxed,
    label: 'Compact',
  },
  {
    bgImage: wideSvg,
    value: ContentWidth.Fluid,
    label: 'Wide',
  },
])

const currentDir = ref(configStore.isAppRTL ? 'rtl' : 'ltr')

const direction = computed(() => [
  {
    bgImage: ltrSvg,
    value: Direction.Ltr,
    label: 'Left to right',
  },
  {
    bgImage: rtlSvg,
    value: Direction.Rtl,
    label: 'Right to left',
  },
])

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
  <VNavigationDrawer
    v-model="isNavDrawerOpen"
    data-allow-mismatch
    temporary
    touchless
    border="none"
    location="end"
    width="400"
    elevation="10"
    :scrim="false"
    class="app-customizer"
  >
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
          @click="isNavDrawerOpen = false"
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
      tag="ul"
      :options="{ wheelPropagation: false }"
    >
      <CustomizerSection
        title="Theming"
        :divider="false"
      >
        <div class="d-flex flex-column gap-2">
          <h6 class="text-h6">
            Primary Color
          </h6>

          <div class="d-flex flex-wrap gap-2">
            <div
              v-for="color in primaryColors"
              :key="color.main"
              class="primary-color-wrapper cursor-pointer rounded"
              :class="vuetifyTheme.current.value.colors.primary === color.main ? 'active' : ''"
              :style="vuetifyTheme.current.value.colors.primary === color.main ? `outline-color: ${color.main}; outline-width:2px;` : ''"
              @click="setPrimaryColor(color)"
            >
              <div
                class="color-swatch"
                :style="{ backgroundColor: color.main }"
              />
            </div>
          </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-4">
          <h6 class="text-h6">
            Light Background
          </h6>

          <div class="d-flex flex-wrap gap-2">
            <div
              v-for="bg in lightBackgroundColors"
              :key="bg.bg"
              class="bg-swatch cursor-pointer rounded"
              :class="currentLightBg?.bg === bg.bg ? 'active' : ''"
              :style="{ backgroundColor: bg.bg }"
              @click="setLightBackground(bg)"
            >
              <VIcon
                v-if="currentLightBg?.bg === bg.bg"
                icon="tabler-check"
                size="16"
              />
            </div>
          </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-4">
          <h6 class="text-h6">
            Dark Background
          </h6>

          <div class="d-flex flex-wrap gap-2">
            <div
              v-for="bg in darkBackgroundColors"
              :key="bg.bg"
              class="bg-swatch cursor-pointer rounded"
              :class="currentDarkBg?.bg === bg.bg ? 'active' : ''"
              :style="{ backgroundColor: bg.bg }"
              @click="setDarkBackground(bg)"
            >
              <VIcon
                v-if="currentDarkBg?.bg === bg.bg"
                icon="tabler-check"
                size="16"
              />
            </div>
          </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-4">
          <h6 class="text-h6">
            Theme
          </h6>

          <CustomRadiosWithImage
            :key="configStore.theme"
            v-model:selected-radio="configStore.theme"
            :radio-content="themeMode"
            :grid-column="{ cols: '4' }"
            class="customizer-skins"
          >
            <template #label="item">
              <span class="text-sm text-medium-emphasis mt-1">{{ item?.label }}</span>
            </template>

            <template #content="{ item }">
              <div
                class="customizer-skins-icon-wrapper d-flex align-center justify-center py-3 w-100"
                style="min-inline-size: 100%;"
              >
                <VIcon
                  size="30"
                  :icon="item.bgImage"
                  color="high-emphasis"
                />
              </div>
            </template>
          </CustomRadiosWithImage>
        </div>

        <div class="d-flex flex-column gap-2 mt-4">
          <h6 class="text-h6">
            Skins
          </h6>

          <CustomRadiosWithImage
            :key="configStore.skin"
            v-model:selected-radio="configStore.skin"
            :radio-content="themeSkin"
            :grid-column="{ cols: '4' }"
          >
            <template #label="item">
              <span class="text-sm text-medium-emphasis">{{ item?.label }}</span>
            </template>
          </CustomRadiosWithImage>
        </div>

        <div
          class="align-center justify-space-between mt-4"
          :class="vuetifyTheme.global.name.value === 'light' && configStore.appContentLayoutNav === AppContentLayoutNav.Vertical ? 'd-flex' : 'd-none'"
        >
          <VLabel
            for="customizer-semi-dark"
            class="text-h6 text-high-emphasis"
          >
            Semi Dark Menu
          </VLabel>

          <div>
            <VSwitch
              id="customizer-semi-dark"
              v-model="configStore.isVerticalNavSemiDark"
              class="ms-2"
            />
          </div>
        </div>
      </CustomizerSection>

      <CustomizerSection title="Layout">
        <div class="d-flex flex-column gap-2">
          <h6 class="text-base font-weight-medium">
            Layout
          </h6>

          <CustomRadiosWithImage
            :key="currentLayout"
            v-model:selected-radio="currentLayout"
            :radio-content="layouts"
            :grid-column="{ cols: '4' }"
          >
            <template #label="item">
              <span class="text-sm text-medium-emphasis">{{ item.label }}</span>
            </template>
          </CustomRadiosWithImage>
        </div>

        <div class="d-flex flex-column gap-2 mt-4">
          <h6 class="text-base font-weight-medium">
            Content
          </h6>

          <CustomRadiosWithImage
            :key="configStore.appContentWidth"
            v-model:selected-radio="configStore.appContentWidth"
            :radio-content="contentWidth"
            :grid-column="{ cols: '4' }"
          >
            <template #label="item">
              <span class="text-sm text-medium-emphasis">{{ item.label }}</span>
            </template>
          </CustomRadiosWithImage>
        </div>

        <div class="d-flex flex-column gap-2 mt-4">
          <h6 class="text-base font-weight-medium">
            Direction
          </h6>

          <CustomRadiosWithImage
            :key="currentDir"
            v-model:selected-radio="currentDir"
            :radio-content="direction"
            :grid-column="{ cols: '4' }"
          >
            <template #label="item">
              <span class="text-sm text-medium-emphasis">{{ item?.label }}</span>
            </template>
          </CustomRadiosWithImage>
        </div>
      </CustomizerSection>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style lang="scss" scoped>
.app-customizer {
  &:not(.v-navigation-drawer--active) {
    visibility: hidden;
    pointer-events: none;
  }
}

.customizer-heading {
  padding-block: 1rem;
  padding-inline: 1.5rem;
}

.color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}

.bg-swatch {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  transition: transform 0.2s;
  
  &:hover {
    transform: scale(1.1);
  }
  
  &.active {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
  }
}

.primary-color-wrapper {
  padding: 4px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  
  &:hover {
    border-color: rgba(var(--v-border-color), 0.22);
  }
  
  &.active {
    outline: 2px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
  }
}
</style>
