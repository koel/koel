<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>Settings</ScreenHeader>
    </template>

    <Tabs v-if="tabs.length" class="-mx-6">
      <TabList>
        <TabButton
          v-for="tab in tabs"
          :id="`settingsTab-${tab.id}`"
          :key="tab.id"
          :aria-controls="`settingsPane-${tab.id}`"
          :data-testid="`settings-tab-${tab.id}`"
          :selected="currentTabId === tab.id"
          @click="currentTabId = tab.id"
        >
          {{ tab.label }}
        </TabButton>
      </TabList>

      <TabPanelContainer>
        <TabPanel
          v-for="tab in tabs"
          v-show="currentTabId === tab.id"
          :id="`settingsPane-${tab.id}`"
          :key="tab.id"
          :aria-labelledby="`settingsTab-${tab.id}`"
        >
          <component :is="tab.component" v-bind="tab.props" />
        </TabPanel>
      </TabPanelContainer>
    </Tabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import type { Component } from 'vue'
import { ref } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import { useBranding } from '@/composables/useBranding'
import { useKoelPlus } from '@/composables/useKoelPlus'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
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

const { currentBranding } = useBranding()
const { isPlus } = useKoelPlus()

const usesLocalStorage = commonStore.state.storage_driver === 'local'

const tabs = applyFilters<SettingsTab[]>(Filter.SETTINGS_TABS, [
  ...(usesLocalStorage ? [{ id: 'library', label: 'Library', component: MediaPathSettingGroup }] : []),
  ...(isPlus.value
    ? [
        { id: 'branding', label: 'Branding', component: BrandingSettingGroup, props: { currentBranding } },
        { id: 'ai', label: 'AI', component: AiSettingGroup },
      ]
    : []),
  { id: 'services', label: 'Services', component: ServicesSettingGroup },
])

const currentTabId = ref(tabs[0]?.id)
</script>
