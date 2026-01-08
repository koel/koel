<template>
  <SideSheetButton
    v-koel-tooltip.left
    :title="shouldNotifyNewVersion ? t('meta.newVersionAvailable') : t('meta.about', { app: appName })"
    @click.prevent="openAboutKoelModal"
  >
    <Icon :icon="faInfoCircle" />
    <span
      v-if="shouldNotifyNewVersion"
      class="absolute w-[10px] aspect-square right-px top-px rounded-full bg-k-highlight"
      data-testid="new-version-indicator"
    />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { faInfoCircle } from '@fortawesome/free-solid-svg-icons'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'
import { useBranding } from '@/composables/useBranding'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const { t } = useI18n()

const { shouldNotifyNewVersion } = useNewVersionNotification()
const { name: appName } = useBranding()

const openAboutKoelModal = () => eventBus.emit('MODAL_SHOW_ABOUT_KOEL')
</script>
