<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>{{ t('screens.settings') }}</ScreenHeader>
    </template>

    <main class="space-y-6">
      <MediaPathSettingGroup open />
      <BrandingSettingGroup v-if="isPlus" :current-branding="currentBranding" />
    </main>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import MediaPathSettingGroup from '@/components/screens/settings/MediaPathSettingGroup.vue'
import BrandingSettingGroup from '@/components/screens/settings/BrandingSettingGroup.vue'

import { useKoelPlus } from '@/composables/useKoelPlus'
import { useBranding } from '@/composables/useBranding'

const { t } = useI18n()
const {
  currentBranding,
} = useBranding()

const { isPlus } = useKoelPlus()
</script>
