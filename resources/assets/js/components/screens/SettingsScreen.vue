<template>
  <ScreenBase scrolls-itself>
    <template #header>
      <ScreenHeader>Settings</ScreenHeader>
    </template>

    <div class="flex flex-col md:flex-row flex-1 min-h-0">
      <SettingsSectionNav
        :model-value="currentSectionId"
        :panel-id="panelId"
        :sections
        class="flex-none"
        @update:model-value="openSection"
      />

      <section
        :id="panelId"
        :key="currentSection.id"
        :aria-labelledby="`settingsSection-${currentSection.id}`"
        class="flex-1 min-w-0 min-h-0 overflow-auto scroll-mask-y p-6"
        role="tabpanel"
        tabindex="0"
      >
        <h2 class="mb-6 text-2xl text-k-fg" data-testid="settings-section-heading">
          {{ currentSection.label }}
        </h2>

        <component :is="currentSection.component" v-bind="currentSection.props" />
      </section>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import type { Component } from 'vue'
import { computed, ref } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import { useBranding } from '@/composables/useBranding'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { usePolicies } from '@/composables/usePolicies'
import { useRouter } from '@/composables/useRouter'
import { usePageTitle } from '@/composables/usePageTitle'
import { defineAsyncComponent } from '@/utils/helpers'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import SettingsSectionNav from '@/components/screens/settings/SettingsSectionNav.vue'
import MediaPathSettingGroup from '@/components/screens/settings/MediaPathSettingGroup.vue'
import BrandingSettingGroup from '@/components/screens/settings/BrandingSettingGroup.vue'
import AiSettingGroup from '@/components/screens/settings/AiSettingGroup.vue'
import ServicesSettingGroup from '@/components/screens/settings/ServicesSettingGroup.vue'

export interface SettingsTab {
  id: string
  label: string
  component: Component
  props?: Record<string, unknown>
}

export type ProfileTab = Omit<SettingsTab, 'props'>

export interface SettingsSection extends SettingsTab {
  group: 'Account' | 'Server'
}

const ProfileSection = defineAsyncComponent(() => import('@/components/profile-preferences/ProfileSection.vue'))
const PreferencesForm = defineAsyncComponent(() => import('@/components/profile-preferences/PreferencesForm.vue'))
const ThemePreferences = defineAsyncComponent(
  () => import('@/components/profile-preferences/theme/ThemePreferences.vue'),
)
const Integrations = defineAsyncComponent(() => import('@/components/profile-preferences/Integrations.vue'))
const OfflineStorage = defineAsyncComponent(() => import('@/components/profile-preferences/OfflineStorage.vue'))
const SubsonicCredentials = defineAsyncComponent(
  () => import('@/components/profile-preferences/SubsonicCredentials.vue'),
)
const SecuritySection = defineAsyncComponent(() => import('@/components/profile-preferences/SecuritySection.vue'))
const QRLogin = defineAsyncComponent(() => import('@/components/profile-preferences/QRLogin.vue'))

const { currentBranding } = useBranding()
const { isPlus } = useKoelPlus()
const { currentUserCan } = usePolicies()

const panelId = 'settingsPanel'

const accountSections: ProfileTab[] = [
  { id: 'profile', label: 'Profile', component: ProfileSection },
  { id: 'preferences', label: 'Preferences', component: PreferencesForm },
  { id: 'themes', label: 'Themes', component: ThemePreferences },
  { id: 'integrations', label: 'Integrations', component: Integrations },
  { id: 'offline', label: 'Offline', component: OfflineStorage },
  { id: 'subsonic', label: 'Subsonic', component: SubsonicCredentials },
  { id: 'security', label: 'Security', component: SecuritySection },
  { id: 'qr', label: 'QR Login', component: QRLogin },
  ...applyFilters<ProfileTab[]>(Filter.ACCOUNT_SETTINGS_TABS, []),
]

const getServerSections = (): SettingsTab[] => {
  if (!currentUserCan.manageSettings()) {
    return []
  }

  const usesLocalStorage = commonStore.state.storage_driver === 'local'

  return applyFilters<SettingsTab[]>(Filter.SERVER_SETTINGS_TABS, [
    ...(usesLocalStorage ? [{ id: 'media-path', label: 'Media Path', component: MediaPathSettingGroup }] : []),
    ...(isPlus.value
      ? [
          { id: 'branding', label: 'Branding', component: BrandingSettingGroup, props: { currentBranding } },
          { id: 'ai', label: 'AI', component: AiSettingGroup },
        ]
      : []),
    { id: 'services', label: 'Services', component: ServicesSettingGroup },
  ])
}

const sections: SettingsSection[] = [
  ...accountSections.map(section => ({ ...section, group: 'Account' as const })),
  ...getServerSections().map(section => ({ ...section, group: 'Server' as const })),
]

const { getRouteParam, go, onScreenActivated, url } = useRouter()

const isAvailableSection = (id: string | undefined): id is string => sections.some(section => section.id === id)

const currentSectionId = ref(sections[0].id)

const currentSection = computed(() => sections.find(section => section.id === currentSectionId.value) ?? sections[0])

usePageTitle().useScreenTitle('Settings', () => currentSection.value.label)

const openSection = (id: string) => go(url('settings', { section: id }))

onScreenActivated('Settings', () => {
  const requestedSectionId = getRouteParam('section')
  currentSectionId.value = isAvailableSection(requestedSectionId) ? requestedSectionId : sections[0].id
})
</script>
