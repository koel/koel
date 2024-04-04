<template>
  <article
    :class="{ playing, external, selected: item.selected }"
    class="song-item text-k-text-secondary border-b border-k-border !max-w-full h-[64px] flex
    items-center transition-[background-color,_box-shadow] ease-in-out duration-200
    focus:rounded-md focus focus-within:rounded-md focus:ring-inset focus:ring-1 focus:!ring-k-accent
    focus-within:ring-inset focus-within:ring-1 focus-within:!ring-k-accent
    hover:bg-white/5 hover:ring-inset hover:ring-1 hover:ring-white/10 hover:rounded-md"
    data-testid="song-item"
    tabindex="0"
    @dblclick.prevent.stop="play"
  >
    <span class="track-number">
      <SoundBars v-if="song.playback_state === 'Playing'" />
      <span v-else class="text-k-text-secondary">{{ song.track || '' }}</span>
    </span>
    <span class="thumbnail">
      <SongThumbnail :song="song" />
    </span>
    <span class="title-artist flex flex-col gap-2 overflow-hidden">
      <span class="title text-k-text-primary !flex gap-2 items-center">
        <ExternalMark v-if="external" class="!inline-block" />
        {{ song.title }}
      </span>
      <span class="artist">
        {{ song.artist_name }}
      </span>
    </span>
    <span class="album">{{ song.album_name }}</span>
    <template v-if="config.collaborative">
      <span class="collaborator">
        <UserAvatar :user="collaborator" width="24" />
      </span>
      <span class="added-at" :title="song.collaboration.added_at">{{ song.collaboration.fmt_added_at }}</span>
    </template>
    <span class="time">{{ fmtLength }}</span>
    <span class="extra">
      <LikeButton :song="song" />
    </span>
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { requireInjection, secondsToHis } from '@/utils'
import { useAuthorization, useKoelPlus } from '@/composables'
import { SongListConfigKey } from '@/symbols'

import LikeButton from '@/components/song/SongLikeButton.vue'
import SoundBars from '@/components/ui/SoundBars.vue'
import SongThumbnail from '@/components/song/SongThumbnail.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'

const [config] = requireInjection<[Partial<SongListConfig>]>(SongListConfigKey, [{}])

const { currentUser } = useAuthorization()
const { isPlus } = useKoelPlus()

const props = defineProps<{ item: SongRow }>()
const { item } = toRefs(props)

const emit = defineEmits<{ (e: 'play', song: Song): void }>()

const song = computed<Song | CollaborativeSong>(() => item.value.song)
const playing = computed(() => ['Playing', 'Paused'].includes(song.value.playback_state!))
const external = computed(() => isPlus.value && song.value.owner_id !== currentUser.value?.id)
const fmtLength = secondsToHis(song.value.length)

const collaborator = computed<Pick<User, 'name' | 'avatar'>>(() => (song.value as CollaborativeSong).collaboration.user)

const play = () => emit('play', song.value)
</script>

<style lang="postcss" scoped>
article {
  &.droppable {
    @apply relative transition-none after:absolute after:w-full after:h-[3px] after:rounded after:bg-k-success after:top-0;

    &.dragover-bottom {
      @apply after:top-auto after:bottom-0;
    }
  }

  &.selected {
    @apply bg-white/10;
  }

  &.playing {
    .title, .track-number, .favorite {
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
