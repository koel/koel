<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>Settings</ScreenHeader>
    </template>

    <main class="space-y-4">
      <MediaPathSettingGroup open />
      <BrandingSettingGroup v-if="isPlus" :current-branding="currentBranding" />
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

const {
  currentBranding,
} = useBranding()

const { isPlus } = useKoelPlus()
</script>
