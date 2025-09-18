<template>
  <div>
    <article
      :class="{ playing, selected: item.selected }"
      class="playable-list-item group gap-4 px-4"
      data-testid="playable-embed-item"
      tabindex="0"
      @dblclick.prevent.stop="play"
    >
      <span class="track-number flex items-center justify-center min-w-5 overflow-hidden">
        <SoundBars v-if="playable.playback_state === 'Playing'" />
        <span v-else class="text-k-text-secondary">
          <template v-if="isSong(playable)">{{ playable.track || '' }}</template>
          <Icon v-else :icon="faPodcast" />
        </span>
      </span>
      <span class="leading-none">
        <PlayableThumbnail :playable @clicked="play" />
      </span>
      <span class="title-artist flex-1 flex flex-col gap-2 overflow-hidden">
        <span class="title text-k-text-primary">{{ playable.title }}</span>
        <span>{{ artist }} - {{ album }}</span>
      </span>
      <span class="font-mono">{{ fmtLength }}</span>
    </article>
  </div>
</template>

<script lang="ts" setup>
import { faPodcast } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { getPlayableProp } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { secondsToHis } from '@/utils/formatters'

import SoundBars from '@/components/ui/SoundBars.vue'
import PlayableThumbnail from '@/components/playable/PlayableThumbnail.vue'

const props = defineProps<{ item: PlayableRow }>()

const emit = defineEmits<{ (e: 'play', playable: Playable): void }>()

const { item } = toRefs(props)

const playable = computed<Playable>(() => item.value.playable)
const playing = computed(() => ['Playing', 'Paused'].includes(playable.value.playback_state!))

const fmtLength = secondsToHis(playable.value.length)
const artist = computed(() => getPlayableProp(playable.value, 'artist_name', 'podcast_author'))
const album = computed(() => getPlayableProp(playable.value, 'album_name', 'podcast_title'))

const play = () => emit('play', playable.value)
</script>

<style lang="postcss" scoped>
article {
  &.playing {
    .title,
    .track-number {
      @apply text-k-accent !important;
    }
  }

  .title-artist {
    span {
      @apply overflow-hidden whitespace-nowrap text-ellipsis block;
    }
  }
}
</style>
