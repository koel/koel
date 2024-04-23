<template>
  <ExtraDrawerButton
    v-koel-tooltip.left
    :title="shouldNotifyNewVersion ? 'New version available!' : 'About Koel'"
    @click.prevent="openAboutKoelModal"
  >
    <Icon :icon="faInfoCircle" />
    <span
      v-if="shouldNotifyNewVersion"
      class="absolute w-[10px] aspect-square right-px top-px rounded-full bg-k-highlight"
      data-testid="new-version-indicator"
    />
  </ExtraDrawerButton>
</template>

<script setup lang="ts">
import { faInfoCircle } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'
import { useNewVersionNotification } from '@/composables'

import ExtraDrawerButton from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawerButton.vue'

const { shouldNotifyNewVersion } = useNewVersionNotification()

const openAboutKoelModal = () => eventBus.emit('MODAL_SHOW_ABOUT_KOEL')
</script>
