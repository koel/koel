<template>
  <FooterButton
    class="w-12! rounded-full border-2 border-solid border-k-fg-70 aspect-square transition-transform! hover:scale-125 text-2xl! has-[.icon-play]:indent-[0.23rem]"
    @click.prevent="togglePlayback"
  >
    <Icon v-if="playing" :icon="faPause" />
    <Icon v-else :icon="faPlay" class="icon-play" />
    <span class="sr-only">{{ label }}</span>
  </FooterButton>
</template>

<script lang="ts" setup>
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { CurrentStreamableKey } from '@/config/symbols'
import { usePlaybackToggle } from '@/composables/usePlaybackToggle'

import FooterButton from '@/components/layout/app-footer/FooterButton.vue'

const streamable = requireInjection(CurrentStreamableKey, ref())
const { togglePlayback } = usePlaybackToggle(streamable)

const playing = computed(() => streamable.value?.playback_state === 'Playing')
const isRadio = computed(() => streamable.value?.type === 'radio-stations')

const label = computed(() => {
  if (isRadio.value) {
    return streamable.value?.playback_state === 'Playing' ? 'Stop streaming' : 'Start streaming'
  }

  return playing.value ? 'Pause' : 'Play or resume'
})
</script>
