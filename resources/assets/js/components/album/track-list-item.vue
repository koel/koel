<template>
  <li :class="{ available: song }" :title="tooltip" @click="play" role="button" tabindex="0">
    <span class="no">{{ index + 1 }}</span>
    <span class="title">{{ track.title }}</span>
    <a
      :href="iTunesUrl"
      v-if="useiTunes && !song"
      target="_blank"
      class="view-on-itunes"
      title="View on iTunes"
    >
      iTunes
    </a>
    <span class="length">{{ track.fmtLength }}</span>
  </li>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs } from 'vue'
import { queueStore, sharedStore, songStore } from '@/stores'
import { auth, playback } from '@/services'

const props = defineProps<{ album: Album, track: AlbumTrack, index: number }>()
const { album, track, index } = toRefs(props)

const useiTunes = ref(sharedStore.state.useiTunes)

const song = computed(() => songStore.guess(track.value.title, album.value))
const tooltip = computed(() => song.value ? 'Click to play' : '')

const iTunesUrl = computed(() => {
  return `${window.BASE_URL}itunes/song/${album.value.id}?q=${encodeURIComponent(track.value.title)}&api_token=${auth.getToken()}`
})

const play = () => {
  if (song.value) {
    queueStore.contains(song.value) || queueStore.queueAfterCurrent(song.value)
    playback.play(song.value)
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
