<template>
  <button
    :style="{ backgroundImage: `url(${defaultCover})` }"
    :title="title"
    class="song-thumbnail w-[48px] aspect-square bg-cover relative rounded overflow-hidden active:scale-95"
    @click.prevent="playOrPause"
  >
    <img
      v-koel-hide-broken-icon
      alt="Cover image"
      :src="src"
      class="w-full aspect-square object-cover"
      loading="lazy"
    >
    <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 z-10" />
    <span
      class="absolute flex opacity-0 items-center justify-center w-[24px] aspect-square rounded-full top-1/2
        left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
    >
      <Icon v-if="song.playback_state === 'Playing'" :icon="faPause" class="text-white" />
      <Icon v-else :icon="faPlay" class="text-white ml-0.5" />
    </span>
  </button>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { defaultCover, getPlayableProp } from '@/utils'
import { playbackService } from '@/services'

const props = defineProps<{ song: Playable }>()
const { song } = toRefs(props)

const src = computed(() => getPlayableProp<string>(song.value, 'album_cover', 'episode_image'))

const play = () => playbackService.play(song.value)

const title = computed(() => {
  if (song.value.playback_state === 'Playing') {
    return 'Pause'
  }

  if (song.value.playback_state === 'Paused') {
    return 'Resume'
  }

  return 'Play'
})

const playOrPause = () => {
  if (song.value.playback_state === 'Stopped') {
    play()
  } else if (song.value.playback_state === 'Paused') {
    playbackService.resume()
  } else {
    playbackService.pause()
  }
}
</script>
