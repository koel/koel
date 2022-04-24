<template>
  <li :class="{ available: song }" :title="tooltip" role="button" tabindex="0" @click="play">
    <span class="title">{{ track.title }}</span>
    <a
      v-if="useiTunes && !song"
      :href="iTunesUrl"
      class="view-on-itunes"
      target="_blank"
      title="View on iTunes"
    >
      iTunes
    </a>
    <span class="length">{{ track.fmtLength }}</span>
  </li>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { queueStore, commonStore, songStore } from '@/stores'
import { authService, playbackService } from '@/services'

const props = defineProps<{ album: Album, track: AlbumTrack }>()
const { album, track } = toRefs(props)

const useiTunes = toRef(commonStore.state, 'useiTunes')

const song = computed(() => songStore.guess(track.value.title, album.value))
const tooltip = computed(() => song.value ? 'Click to play' : '')

const iTunesUrl = computed(() => {
  return `${window.BASE_URL}itunes/song/${album.value.id}?q=${encodeURIComponent(track.value.title)}&api_token=${authService.getToken()}`
})

const play = () => {
  if (song.value) {
    queueStore.queueIfNotQueued(song.value)
    playbackService.play(song.value)
  }
}
</script>

<style lang="scss" scoped>
[role=button] {
  &:focus {
    span.title {
      color: var(--color-highlight);
    }
  }

  a.view-on-itunes {
    display: inline-block;
    border-radius: 3px;
    font-size: .8rem;
    padding: 0 5px;
    color: var(--color-text-primary);
    background: rgba(255, 255, 255, .1);
    height: 20px;
    line-height: 20px;
    margin-left: 4px;

    &:hover, &:focus {
      background: linear-gradient(27deg, #fe5c52 0%, #c74bd5 50%, #2daaff 100%);
      color: var(--color-text-primary);
    }

    &:active {
      box-shadow: inset 0px 5px 5px -5px #000;
    }
  }
}
</style>
