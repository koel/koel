<template>
  <div>
    <h4
      v-if="isSong(playable) && showDisc && playable.disc"
      class="title text-k-text-primary !flex gap-2 p-2 uppercase pl-5"
    >
      Disc {{ playable.disc }}
    </h4>

    <article
      :class="{ playing, selected: item.selected }"
      class="song-item group pl-2 playable-list-item"
      data-testid="song-item"
      tabindex="0"
      @dblclick.prevent.stop="play"
    >
      <span v-if="shouldShowColumn('track')" class="track-number">
        <SoundBars v-if="playable.playback_state === 'Playing'" />
        <span v-else class="text-k-text-secondary">
          <template v-if="isSong(playable)">{{ playable.track || '' }}</template>
          <Icon v-else :icon="faPodcast" />
        </span>
      </span>
      <span class="thumbnail leading-none">
        <PlayableThumbnail :playable @clicked="play" />
      </span>
      <span class="title-artist flex flex-col gap-2 overflow-hidden">
        <span class="title text-k-text-primary !flex gap-2 items-center">
          <ExternalMark v-if="external" />
          <span class="flex-1">{{ playable.title }}</span>
        </span>
        <span class="artist">{{ artist }}</span>
      </span>
      <span v-if="shouldShowColumn('album')" class="album">{{ album }}</span>
      <template v-if="config.collaborative && isSong(playable) && playable.collaboration">
        <span class="collaborator">
          <UserAvatar :user="collaborator" width="24" />
        </span>
        <span :title="String(playable.collaboration.added_at)" class="added-at">
          {{ playable.collaboration.fmt_added_at }}
        </span>
      </template>
      <template v-if="isSong(playable)">
        <span v-if="shouldShowColumn('genre')" class="genre">{{ playable.genre || '—' }}</span>
        <span v-if="shouldShowColumn('year')" class="year">{{ playable.year || '—' }}</span>
      </template>
      <span v-if="shouldShowColumn('duration')" class="time font-mono">{{ fmtLength }}</span>
      <span class="extra">
        <FavoriteButton :favorite="playable.favorite" @toggle="toggleFavorite" />
      </span>
    </article>
  </div>
</template>

<script lang="ts" setup>
import { faPodcast } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { getPlayableProp, requireInjection } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { secondsToHis } from '@/utils/formatters'
import { usePlayableListColumnVisibility } from '@/composables/usePlayableListColumnVisibility'
import { PlayableListConfigKey } from '@/symbols'
import { playableStore } from '@/stores/playableStore'

import SoundBars from '@/components/ui/SoundBars.vue'
import PlayableThumbnail from '@/components/playable/PlayableThumbnail.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'

const props = withDefaults(defineProps<{ item: PlayableRow, showDisc?: boolean }>(), {
  showDisc: false,
})

const emit = defineEmits<{ (e: 'play', playable: Playable): void }>()

const [config] = requireInjection<[Partial<PlayableListConfig>]>(PlayableListConfigKey, [{}])

const { shouldShowColumn } = usePlayableListColumnVisibility()

const { item } = toRefs(props)

const playable = computed<Playable>(() => item.value.playable)
const playing = computed(() => ['Playing', 'Paused'].includes(playable.value.playback_state!))
const external = computed(() => isSong(playable.value) && playable.value.is_external)

const fmtLength = secondsToHis(playable.value.length)
const artist = computed(() => getPlayableProp(playable.value, 'artist_name', 'podcast_author'))
const album = computed(() => getPlayableProp(playable.value, 'album_name', 'podcast_title'))

const collaborator = computed<Pick<User, 'name' | 'avatar'>>(
  () => (playable.value as Song).collaboration!.user,
)

const play = () => emit('play', playable.value)

const toggleFavorite = () => playableStore.toggleFavorite(playable.value)
</script>

<style lang="postcss" scoped>
article {
  &.droppable {
    @apply relative transition-none after:absolute after:w-full after:h-[3px] after:rounded after:bg-k-success after:top-0;

    &.dragover-bottom {
      @apply after:top-auto after:bottom-0;
    }
  }

  &.playing {
    .title,
    .track-number,
    .favorite {
      @apply text-k-accent !important;
    }
  }

  .title-artist {
    span {
      @apply overflow-hidden whitespace-nowrap text-ellipsis block;
    }
  }

  button {
    @apply text-current;
  }
}
</style>
