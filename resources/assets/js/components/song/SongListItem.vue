<template>
  <div
    :class="{ playing, external, selected: item.selected }"
    class="song-item"
    data-testid="song-item"
    tabindex="0"
    @dblclick.prevent.stop="play"
  >
    <span class="track-number">
      <SoundBars v-if="song.playback_state === 'Playing'" />
      <span v-else class="text-secondary">{{ song.track || '' }}</span>
    </span>
    <span class="thumbnail">
      <SongThumbnail :song="song" />
    </span>
    <span class="title-artist">
      <span class="title text-primary">
        <span v-if="external" class="external-mark">
          <Icon :icon="faSquareUpRight" />
        </span>
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
  </div>
</template>

<script lang="ts" setup>
import { faSquareUpRight } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { requireInjection, secondsToHis } from '@/utils'
import { useAuthorization, useKoelPlus } from '@/composables'
import { SongListConfigKey } from '@/symbols'

import LikeButton from '@/components/song/SongLikeButton.vue'
import SoundBars from '@/components/ui/SoundBars.vue'
import SongThumbnail from '@/components/song/SongThumbnail.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'

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

<style lang="postcss">
.song-item {
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-bg-secondary);
  max-width: 100% !important; /* overriding .item */
  height: 64px;
  display: flex;
  align-items: center;
  transition: background-color .2s ease-in-out, box-shadow .2s ease-in-out;

  &:focus, &:focus-within {
    box-shadow: 0 0 1px 1px var(--color-accent) inset !important;
    border-radius: 4px;
  }

  .external-mark {
    display: inline-block !important;
    vertical-align: bottom;
    margin-right: .2rem;
    opacity: .5;
  }

  @media (hover: none) {
    .cover {
      .control {
        display: flex;
      }

      &::before {
        opacity: .7;
      }
    }
  }

  &:hover {
    background: rgba(255, 255, 255, .05);
    box-shadow: 0 0 1px 1px rgba(255, 255, 255, .1) inset;
    border-radius: 5px;
  }

  &.selected {
    background-color: rgba(255, 255, 255, .08);
  }

  &.playing {
    .title, .track-number, .favorite {
      color: var(--color-accent) !important;
    }
  }

  .title-artist {
    display: flex;
    flex-direction: column;
    gap: 5px;
    overflow: hidden;

    span {
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      display: block;
    }
  }

  button {
    color: currentColor;
  }
}
</style>
