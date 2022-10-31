<template>
  <div
    class="track-list-item"
    :class="{ active, available: matchedSong }"
    :title="tooltip"
    tabindex="0"
    @click="play"
  >
    <span class="title">{{ track.title }}</span>
    <AppleMusicButton v-if="useAppleMusic && !matchedSong" :url="iTunesUrl"/>
    <span class="length">{{ fmtLength }}</span>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, Ref, toRefs } from 'vue'
import { queueStore, songStore } from '@/stores'
import { authService, playbackService } from '@/services'
import { useThirdPartyServices } from '@/composables'
import { requireInjection, secondsToHis } from '@/utils'
import { SongsKey } from '@/symbols'

const AppleMusicButton = defineAsyncComponent(() => import('@/components/ui/AppleMusicButton.vue'))

const props = defineProps<{ album: Album, track: AlbumTrack }>()
const { album, track } = toRefs(props)

const { useAppleMusic } = useThirdPartyServices()

const songsToMatchAgainst = requireInjection<Ref<Song[]>>(SongsKey)

const matchedSong = computed(() => songStore.match(track.value.title, songsToMatchAgainst.value))
const tooltip = computed(() => matchedSong.value ? 'Click to play' : '')
const fmtLength = computed(() => secondsToHis(track.value.length))

const active = computed(() => matchedSong.value && matchedSong.value.playback_state !== 'Stopped')

const iTunesUrl = computed(() => {
  return `${window.BASE_URL}itunes/song/${album.value.id}?q=${encodeURIComponent(track.value.title)}&api_token=${authService.getToken()}`
})

const play = () => {
  if (matchedSong.value) {
    queueStore.queueIfNotQueued(matchedSong.value)
    playbackService.play(matchedSong.value)
  }
}
</script>

<style lang="scss" scoped>
.track-list-item {
  display: flex;
  flex: 1;
  gap: 4px;

  &:focus, &.active {
    span.title {
      color: var(--color-highlight);
    }
  }

  .title {
    flex: 1;
  }

  .length {
    flex: 0 0 44px;
    text-align: right;
    opacity: .5;
  }

  &.available {
    color: var(--color-text-primary);
    cursor: pointer;

    &:hover {
      color: var(--color-highlight);
    }
  }
}
</style>
