<template>
  <div
    :class="{ active, available: matchedSong }"
    :title="tooltip"
    class="track-list-item flex flex-1 gap-1"
    tabindex="0"
    @click="play"
  >
    <span class="flex-1">{{ track.title }}</span>
    <AppleMusicButton v-if="useAppleMusic && !matchedSong" :url="iTunesUrl" />
    <span class="w-14 text-right opacity-50">{{ fmtLength }}</span>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, Ref, toRefs } from 'vue'
import { songStore } from '@/stores'
import { authService, playbackService } from '@/services'
import { useThirdPartyServices } from '@/composables'
import { requireInjection, secondsToHis } from '@/utils'
import { PlayablesKey } from '@/symbols'

const AppleMusicButton = defineAsyncComponent(() => import('@/components/ui/AppleMusicButton.vue'))

const props = defineProps<{ album: Album, track: AlbumTrack }>()
const { album, track } = toRefs(props)

const { useAppleMusic } = useThirdPartyServices()

const songsToMatchAgainst = requireInjection<Ref<Song[]>>(PlayablesKey)

const matchedSong = computed(() => songStore.match(track.value.title, songsToMatchAgainst.value))
const tooltip = computed(() => matchedSong.value ? 'Click to play' : '')
const fmtLength = computed(() => secondsToHis(track.value.length))

const active = computed(() => matchedSong.value && matchedSong.value.playback_state !== 'Stopped')

const iTunesUrl = computed(() => {
  return `${window.BASE_URL}itunes/song/${album.value.id}?q=${encodeURIComponent(track.value.title)}&api_token=${authService.getApiToken()}`
})

const play = () => matchedSong.value && playbackService.play(matchedSong.value)
</script>

<style lang="postcss" scoped>
.track-list-item {
  &:focus, &.active {
    span.title {
      @apply text-k-highlight;
    }
  }

  &.available {
    @apply cursor-pointer text-k-text-primary;

    &:hover {
      @apply text-k-highlight;
    }
  }
}
</style>
