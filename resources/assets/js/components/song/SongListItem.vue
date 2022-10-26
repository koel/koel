<template>
  <div
    :class="{ playing, selected: item.selected }"
    class="song-item"
    data-testid="song-item"
    @dblclick.prevent.stop="play"
    tabindex="0"
  >
    <span v-if="columns.includes('track')" class="track-number">
      <SoundBars v-if="song.playback_state === 'Playing'"/>
      <span class="text-secondary" v-else>{{ song.track || '' }}</span>
    </span>
    <span class="thumbnail">
      <SongThumbnail :song="song"/>
    </span>
    <span v-if="columns.includes('title')" class="title text-primary">{{ song.title }}</span>
    <span v-if="columns.includes('artist')" class="artist">{{ song.artist_name }}</span>
    <span v-if="columns.includes('album')" class="album">{{ song.album_name }}</span>
    <span v-if="columns.includes('length')" class="time">{{ fmtLength }}</span>
    <span class="favorite">
      <LikeButton :song="song"/>
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { playbackService } from '@/services'
import { queueStore } from '@/stores'
import { secondsToHis } from '@/utils'

import LikeButton from '@/components/song/SongLikeButton.vue'
import SoundBars from '@/components/ui/SoundBars.vue'
import SongThumbnail from '@/components/song/SongThumbnail.vue'

const props = defineProps<{ item: SongRow, columns: SongListColumn[] }>()
const { item, columns } = toRefs(props)

const song = computed(() => item.value.song)
const playing = computed(() => ['Playing', 'Paused'].includes(song.value.playback_state!))
const fmtLength = secondsToHis(song.value.length)

const play = () => {
  queueStore.queueIfNotQueued(song.value)
  playbackService.play(song.value)
}
</script>

<style lang="scss">
.song-item {
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-bg-secondary);
  max-width: 100% !important; // overriding .item
  height: 64px;
  display: flex;
  align-items: center;

  &:focus, &:focus-within {
    box-shadow: 0 0 1px 1px var(--color-accent) inset;
    border-radius: 4px;
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
  }

  &.selected {
    background-color: rgba(255, 255, 255, .08);
  }

  &.playing {
    color: var(--color-accent);

    .title {
      color: var(--color-accent) !important;
    }
  }

  button {
    color: currentColor;
  }
}
</style>
