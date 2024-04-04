<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>Profile &amp; Preferences</ScreenHeader>
    </template>

    <Tabs class="-mx-6">
      <TabList>
        <TabButton
          :selected="currentTab === 'profile'"
          aria-controls="profilePaneProfile"
          @click="currentTab = 'profile'"
        >
          Profile
        </TabButton>
        <TabButton
          :selected="currentTab === 'preferences'"
          aria-controls="profilePanePreferences"
          @click="currentTab = 'preferences'"
        >
          Preferences
        </TabButton>
        <TabButton
          :selected="currentTab === 'themes'"
          aria-controls="profilePaneThemes"
          @click="currentTab = 'themes'"
        >
          Themes
        </TabButton>
        <TabButton
          :selected="currentTab === 'integrations'"
          aria-controls="profilePaneIntegrations"
          @click="currentTab = 'integrations'"
        >
          Integrations
        </TabButton>
        <TabButton
          :selected="currentTab === 'qr'"
          aria-controls="profilePaneQr"
          @click="currentTab = 'qr'"
        >
          <QrCodeIcon :size="16" />
        </TabButton>
      </TabList>

      <TabPanelContainer>
        <TabPanel v-show="currentTab === 'profile'" id="profilePaneProfile" aria-labelledby="profilePaneProfile">
          <ProfileForm />
        </TabPanel>

        <TabPanel
          v-if="currentTab === 'preferences'"
          id="profilePanePreferences"
          aria-labelledby="profilePanePreferences"
        >
          <PreferencesForm />
        </TabPanel>

        <TabPanel v-if="currentTab === 'themes'" id="profilePaneThemes" aria-labelledby="profilePaneThemes">
          <ThemeList />
        </TabPanel>

        <TabPanel
          v-if="currentTab === 'integrations'"
          id="profilePaneIntegrations"
          aria-labelledby="profilePaneIntegrations"
        >
          <Integrations />
        </TabPanel>

        <TabPanel v-if="currentTab === 'qr'" id="profilePaneQr" aria-labelledby="profilePaneQr">
          <QRLogin />
        </TabPanel>
      </TabPanelContainer>
    </Tabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { QrCodeIcon } from 'lucide-vue-next'
import { defineAsyncComponent, ref, watch } from 'vue'
import { useLocalStorage } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'

const ProfileForm = defineAsyncComponent(() => import('@/components/profile-preferences/ProfileForm.vue'))
const PreferencesForm = defineAsyncComponent(() => import('@/components/profile-preferences/PreferencesForm.vue'))
const ThemeList = defineAsyncComponent(() => import('@/components/profile-preferences/ThemeList.vue'))
const Integrations = defineAsyncComponent(() => import('@/components/profile-preferences/Integrations.vue'))
const QRLogin = defineAsyncComponent(() => import('@/components/profile-preferences/QRLogin.vue'))

const { get, set } = useLocalStorage()

const currentTab = ref(get<'profile' | 'preferences' | 'themes' | 'integrations' | 'qr'>('profileScreenTab', 'profile'))

watch(currentTab, tab => set('profileScreenTab', tab))
</script>
