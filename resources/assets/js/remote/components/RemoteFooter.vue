<template>
  <footer class="h-[18vh] w-screen flex justify-around items-center border-t border-solid border-t-white/10 py-4">
    <button
      class="text-[5vmin] has-[.yep]:text-k-love"
      data-testid="btn-toggle-favorite"
      @click.prevent="toggleFavorite"
    >
      <Icon :class="streamable.favorite && 'yep'" :icon="streamable.favorite ? faHeart : faEmptyHeart" />
    </button>

    <button
      :class="canRewindAndFastForward || 'cursor-not-allowed opacity-50'"
      class="text-[6vmin]"
      data-testid="btn-play-prev"
      @click.prevent="playPrev"
    >
      <Icon :icon="faStepBackward" />
    </button>

    <button
      class="text-[7vmin] w-[16vmin] aspect-square border border-solid border-k-text-primary rounded-full flex
      items-center justify-center has-[.paused]:pl-[4px]"
      data-testid="btn-toggle-playback"
      @click.prevent="togglePlayback"
    >
      <Icon :class="playing || 'paused'" :icon="playing ? faPause : faPlay" />
    </button>

    <button
      :class="canRewindAndFastForward || 'cursor-not-allowed opacity-50'"
      class="text-[6vmin]"
      data-testid="btn-play-next"
      @click.prevent="playNext"
    >
      <Icon :icon="faStepForward" />
    </button>

    <VolumeControl class="text-[5vmin]" />
  </footer>
</template>

<script lang="ts" setup>
import { faHeart, faPause, faPlay, faStepBackward, faStepForward } from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed, toRefs } from 'vue'
import { socketService } from '@/services/socketService'
import { isRadioStation } from '@/utils/typeGuards'

import VolumeControl from '@/remote/components/VolumeControl.vue'

const props = defineProps<{ streamable: Streamable }>()
const { streamable } = toRefs(props)

const toggleFavorite = () => {
  streamable.value.favorite = !streamable.value.favorite
  socketService.broadcast('SOCKET_TOGGLE_FAVORITE')
}

const playing = computed(() => streamable.value.playback_state === 'Playing')
const canRewindAndFastForward = computed(() => !isRadioStation(streamable.value))

const togglePlayback = () => {
  streamable.value.playback_state = streamable.value.playback_state === 'Playing' ? 'Paused' : 'Playing'
  socketService.broadcast('SOCKET_TOGGLE_PLAYBACK')
}

const playNext = () => socketService.broadcast('SOCKET_PLAY_NEXT')
const playPrev = () => socketService.broadcast('SOCKET_PLAY_PREV')
</script>

<style lang="postcss" scoped>
a {
  @apply text-k-text-primary active:opacity-80;
}
</style>
