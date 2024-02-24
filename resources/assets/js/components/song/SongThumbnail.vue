<template>
  <div :style="{ backgroundImage: `url(${defaultCover})` }" class="cover">
    <img
      v-koel-hide-broken-icon
      :alt="song.album_name"
      :src="song.album_cover"
      class="pointer-events-none"
      loading="lazy"
    >
    <a :title="title" class="control" role="button" @click.prevent="changeSongState">
      <Icon :icon="song.playback_state === 'Playing' ? faPause : faPlay" class="text-highlight" />
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

<style lang="scss" scoped>
.cover {
  width: 48px;
  min-width: 48px;
  aspect-ratio: 1/1;
  background-size: cover;
  position: relative;
  border-radius: 4px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
  }

  &::before {
    content: " ";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background: #000;
    opacity: 0;
    z-index: 1;

    @media (hover: none) {
      opacity: .7;
    }
  }

  .control {
    border-radius: 50%;
    width: 28px;
    height: 28px;
    background: rgba(0, 0, 0, .5);
    font-size: 1rem;
    z-index: 1;
    display: none;
    color: var(--color-text-primary);
    transition: .3s;
    justify-content: center;
    align-items: center;

    @media (hover: none) {
      display: flex;
    }
  }
}
</style>
