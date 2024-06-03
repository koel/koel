<template>
  <footer class="h-[18vh] w-screen flex justify-around items-center border-t border-solid border-t-white/10 py-4">
    <button
      class="text-[5vmin] has-[.yep]:text-k-love"
      data-testid="btn-toggle-favorite"
      @click.prevent="toggleFavorite"
    >
      <Icon :class="playable.liked && 'yep'" :icon="playable.liked ? faHeart : faEmptyHeart" />
    </button>

    <button class="text-[6vmin]" data-testid="btn-play-prev" @click.prevent="playPrev">
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

    <button class="text-[6vmin]" data-testid="btn-play-next" @click.prevent="playNext">
      <Icon :icon="faStepForward" />
    </button>

    <VolumeControl class="text-[5vmin]" />
  </footer>
</template>

<script lang="ts" setup>
import { faHeart, faPause, faPlay, faStepBackward, faStepForward, } from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed, toRefs } from 'vue'
import { socketService } from '@/services'

import VolumeControl from '@/remote/components/VolumeControl.vue'

const props = defineProps<{ playable: Playable }>()
const { playable } = toRefs(props)

const toggleFavorite = () => {
  playable.value.liked = !playable.value.liked
  socketService.broadcast('SOCKET_TOGGLE_FAVORITE')
}

const playing = computed(() => playable.value.playback_state === 'Playing')

const togglePlayback = () => {
  playable.value.playback_state = playable.value.playback_state === 'Playing' ? 'Paused' : 'Playing'
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
