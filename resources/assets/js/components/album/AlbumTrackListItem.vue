<template>
  <div
    :class="{ active, available: matchedSong }"
    class="track-list-item flex flex-1 gap-1"
    tabindex="0"
    @click="play"
    @keydown.self.enter.space.prevent="play"
  >
    <span class="flex-1">{{ track.title }}</span>
    <AppleMusicButton v-if="useAppleMusic && !matchedSong" :url="iTunesUrl" />
    <span class="w-14 text-right text-k-fg-50">{{ fmtLength }}</span>
  </div>
</template>

<script lang="ts" setup>
import type { Ref } from 'vue'
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { authService } from '@/services/authService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { requireInjection } from '@/utils/helpers'
import { secondsToHis } from '@/utils/formatters'
import { PlayablesKey } from '@/config/symbols'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ album: Album; track: AlbumTrack }>()

const AppleMusicButton = defineAsyncComponent(() => import('@/components/ui/AppleMusicButton.vue'))

const { album, track } = toRefs(props)

const { useAppleMusic } = useThirdPartyServices()

const songsToMatchAgainst = requireInjection<Ref<Song[]>>(PlayablesKey)

const matchedSong = computed(() => playableStore.matchSongsByTitle(track.value.title, songsToMatchAgainst.value))
const fmtLength = computed(() => secondsToHis(track.value.length))

const active = computed(() => matchedSong.value && matchedSong.value.playback_state !== 'Stopped')

const iTunesUrl = computed(() => {
  return `${window.KOEL.base_url}itunes/song/${album.value.id}?q=${encodeURIComponent(track.value.title)}&api_token=${authService.getApiToken()}`
})

const play = () => matchedSong.value && playback().play(matchedSong.value)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.track-list-item {
  &:focus,
  &.active {
    span.title {
      @apply text-k-highlight;
    }
  }

  &.available {
    @apply cursor-pointer text-k-fg;

    &:hover {
      @apply text-k-highlight;
    }
  }
}
</style>
