<template>
  <section id="profileWrapper">
    <ScreenHeader>Profile &amp; Preferences</ScreenHeader>

    <div class="main-scroll-wrap">
      <main class="tabs">
        <div class="clear" role="tablist">
          <button
            role="tab"
            :aria-selected="currentTab === 'profile'"
            aria-controls="profilePaneProfile"
            @click.prevent="currentTab = 'profile'"
          >
            Profile
          </button>
          <button
            role="tab"
            :aria-selected="currentTab === 'preferences'"
            aria-controls="profilePanePreferences"
            @click.prevent="currentTab = 'preferences'"
          >
            Preferences
          </button>
          <button
            role="tab"
            :aria-selected="currentTab === 'themes'"
            aria-controls="profilePaneThemes"
            @click.prevent="currentTab = 'themes'"
          >
            Themes
          </button>
          <button
            role="tab"
            :aria-selected="currentTab === 'integrations'"
            aria-controls="profilePaneIntegrations"
            @click.prevent="currentTab = 'integrations'"
          >
            Integrations
          </button>
          <button
            role="tab"
            :aria-selected="currentTab === 'qr'"
            aria-controls="profilePaneQr"
            @click.prevent="currentTab = 'qr'"
          >
            <QrCodeIcon :size="16" />
          </button>
        </div>

        <div class="panes">
          <div
            v-show="currentTab === 'profile'"
            id="profilePaneProfile"
            role="tabpanel"
            aria-labelledby="profilePaneProfile"
            tabindex="0"
          >
            <ProfileForm />
          </div>

          <div
            v-if="currentTab === 'preferences'"
            id="profilePanePreferences"
            role="tabpanel"
            aria-labelledby="profilePanePreferences"
            tabindex="0"
          >
            <PreferencesForm />
          </div>

          <div
            v-if="currentTab === 'themes'"
            id="profilePaneThemes"
            role="tabpanel"
            tabindex="0"
            aria-labelledby="profilePaneThemes"
          >
            <ThemeList />
          </div>

          <div
            v-if="currentTab === 'integrations'"
            id="profilePaneIntegrations"
            role="tabpanel"
            tabindex="0"
            aria-labelledby="profilePaneIntegrations"
          >
            <Integrations />
          </div>

          <div
            v-if="currentTab === 'qr'"
            id="profilePaneQr"
            role="tabpanel"
            aria-labelledby="profilePaneQr"
            tabindex="0"
          >
            <QRLogin />
          </div>
        </div>
      </main>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { QrCodeIcon } from 'lucide-vue-next'
import { defineAsyncComponent, ref, watch } from 'vue'
import { useLocalStorage } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'

const ProfileForm = defineAsyncComponent(() => import('@/components/profile-preferences/ProfileForm.vue'))
const PreferencesForm = defineAsyncComponent(() => import('@/components/profile-preferences/PreferencesForm.vue'))
const ThemeList = defineAsyncComponent(() => import('@/components/profile-preferences/ThemeList.vue'))
const Integrations = defineAsyncComponent(() => import('@/components/profile-preferences/Integrations.vue'))
const QRLogin = defineAsyncComponent(() => import('@/components/profile-preferences/QRLogin.vue'))

const { get, set } = useLocalStorage()

const currentTab = ref(get<'profile' | 'preferences' | 'themes' | 'integrations' | 'qr'>('profileScreenTab', 'profile'))

watch(currentTab, tab => set('profileScreenTab', tab))
</script>
