<template>
  <div
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="song-thumbnail group w-[48px] min-w-[48px] aspect-square bg-cover relative rounded overflow-hidden flex
    items-center justify-center
    before:absolute before:w-full before:h-full before:pointer-events-none before:z-[1]
    before:left-0 before:top-0 before:bg-black before:opacity-0 hover:before:opacity-70"
  >
    <img
      v-koel-hide-broken-icon
      :alt="song.album_name"
      :src="song.album_cover"
      class="w-full h-full object-cover absolute left-0 top-0 pointer-events-none"
      loading="lazy"
    >
    <a
      :title="title"
      class="w-7 h-7 text-base z-[1] text-k-text-primary duration-300 justify-center
      items-center rounded-full bg-black/50 pl-0.5 flex opacity-0 group-hover:opacity-100"
      role="button"
      @click.prevent="changeSongState"
    >
      <Icon :icon="song.playback_state === 'Playing' ? faPause : faPlay" class="text-k-highlight" />
    </a>
  </div>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { defaultCover } from '@/utils'
import { playbackService } from '@/services'
import { queueStore } from '@/stores'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const play = () => {
  queueStore.queueIfNotQueued(song.value)
  playbackService.play(song.value)
}

const title = computed(() => {
  if (song.value.playback_state === 'Playing') {
    return 'Pause'
  }

  if (song.value.playback_state === 'Paused') {
    return 'Resume'
  }

  return 'Play'
})

const changeSongState = () => {
  if (song.value.playback_state === 'Stopped') {
    play()
  } else if (song.value.playback_state === 'Paused') {
    playbackService.resume()
  } else {
    playbackService.pause()
  }
}
</script>
