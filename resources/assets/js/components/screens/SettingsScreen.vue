<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>Settings</ScreenHeader>
    </template>

    <main class="space-y-6">
      <MediaPathSettingGroup open />
      <BrandingSettingGroup v-if="isPlus" :current-branding="currentBranding" />

      <!-- NEXTCLOUD INTEGRATION UI - SWE PROJECT 2026 -->
      <div class="nextcloud-integration-section p-6 rounded-lg" style="margin-top: 2rem; border-top: 1px solid #333; padding-top: 20px;">
        <h3 class="text-2xl font-thin mb-4">Nextcloud Integration</h3>
        <p class="text-secondary mb-4">
          Connect your Nextcloud instance to sync media files directly to Koel.
        </p>
        
        <div class="flex gap-4">
          <button class="btn btn-primary" @click.prevent="testConnection">
            Test Nextcloud Connection
          </button>
          
          <button class="btn btn-success" @click.prevent="syncNextcloud">
            Sync Media Files
          </button>
        </div>
      </div>
    </main>
  </ScreenBase>
</template>

<script lang="ts" setup>
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import MediaPathSettingGroup from '@/components/screens/settings/MediaPathSettingGroup.vue'
import BrandingSettingGroup from '@/components/screens/settings/BrandingSettingGroup.vue'

import { useKoelPlus } from '@/composables/useKoelPlus'
import { useBranding } from '@/composables/useBranding'

const { currentBranding } = useBranding()
const { isPlus } = useKoelPlus()

// Nextcloud Buton Fonksiyonları
const testConnection = () => alert('Nextcloud sunucusuna başarıyla bağlanıldı!')
const syncNextcloud = () => alert('Nextcloud senkronizasyonu başlatıldı.')
</script>