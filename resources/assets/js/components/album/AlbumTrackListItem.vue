<template>
  <li :class="{ available: song }" :title="tooltip" tabindex="0" @click="play">
    <span class="title">{{ track.title }}</span>
    <AppleMusicButton v-if="useAppleMusic && !song" :url="iTunesUrl"/>
    <span class="length">{{ track.fmtLength }}</span>
  </li>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { queueStore, songStore } from '@/stores'
import { authService, playbackService } from '@/services'
import { useThirdPartyServices } from '@/composables'

const AppleMusicButton = defineAsyncComponent(() => import('@/components/ui/AppleMusicButton.vue'))

const props = defineProps<{ album: Album, track: AlbumTrack }>()
const { album, track } = toRefs(props)

const { useAppleMusic } = useThirdPartyServices()

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
li {
  span.title {
    margin-right: 5px;
  }

  &:focus {
    span.title {
      color: var(--color-highlight);
    }
  }
}
</style>
