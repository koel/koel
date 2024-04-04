<template>
  <footer
    class="h-[18vh] w-screen flex justify-around items-center border-t border-solid border-t-white/10 py-4 text-[5vmin]"
  >
    <a class="has-[.yep]:text-k-love" @click.prevent="toggleFavorite">
      <Icon :icon="song.liked ? faHeart : faEmptyHeart" :class="song.liked && 'yep'" />
    </a>

    <a class="text-[6vmin]" @click="playPrev">
      <Icon :icon="faStepBackward" />
    </a>

    <a
      class="text-[7vmin] w-[16vmin] aspect-square border border-solid border-k-text-primary rounded-full flex
      items-center justify-center has-[.paused]:pl-[4px]"
      @click.prevent="togglePlayback"
    >
      <Icon :icon="playing ? faPause : faPlay" :class="playing || 'paused'" />
    </a>

    <a class="text-[6vmin]" @click.prevent="playNext">
      <Icon :icon="faStepForward" />
    </a>

    <VolumeControl />
  </footer>
</template>

<script setup lang="ts">
import {
  faHeart,
  faPause,
  faPlay,
  faStepBackward,
  faStepForward,
} from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed, toRefs } from 'vue'
import { socketService } from '@/services'

import VolumeControl from '@/remote/components/VolumeControl.vue'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const toggleFavorite = () => {
  song.value.liked = !song.value.liked
  socketService.broadcast('SOCKET_TOGGLE_FAVORITE')
}

const playing = computed(() => song.value.playback_state === 'Playing')

const togglePlayback = () => {
  song.value.playback_state = song.value.playback_state === 'Playing' ? 'Paused' : 'Playing'
  socketService.broadcast('SOCKET_TOGGLE_PLAYBACK')
}

const playNext = () => socketService.broadcast('SOCKET_PLAY_NEXT')
const playPrev = () => socketService.broadcast('SOCKET_PLAY_PREV')
</script>

<style scoped lang="postcss">
a {
  @apply text-k-text-primary active:opacity-80;
}
</style>
