<template>
  <div class="song-info" :class="{ playing: song?.playback_state === 'Playing' }">
    <span :style="{ backgroundImage: `url('${cover}')` }" class="album-thumb" />
    <div v-if="song" class="meta">
      <h3 class="title">{{ song.title }}</h3>
      <a :href="`/#/artist/${song.artist_id}`" class="artist">{{ song.artist_name }}</a>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { defaultCover, requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'

const song = requireInjection(CurrentSongKey, ref())

const cover = computed(() => song.value?.album_cover || defaultCover)
</script>

<style lang="scss" scoped>
.song-info {
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: 320px;
  gap: 1rem;

  @media screen and (max-width: 768px) {
    width: 84px;
  }

  .album-thumb {
    display: block;
    height: 75%;
    aspect-ratio: 1;
    border-radius: 50%;
    background-size: cover;

    @media screen and (max-width: 768px) {
      height: 55%;
    }

    :fullscreen & {
      height: 5rem;
    }
  }

  .meta {
    overflow: hidden;

    > * {
      text-overflow: ellipsis;
      overflow: hidden;
      white-space: nowrap;
    }

    @media screen and (max-width: 768px) {
      display: none;
    }

    :fullscreen & {
      margin-top: -18rem;
      transform-origin: left bottom;
      position: absolute;
      overflow: hidden;
      width: 87vw;

      .title {
        font-size: 3rem;
        margin-bottom: .4rem;
        line-height: 1.2;
        font-weight: var(--font-weight-bold);
      }

      .artist {
        font-size: 1.6rem;
        width: fit-content;
        line-height: 1.2;
      }
    }
  }

  .artist {
    display: block;
    font-size: .9rem;
  }

  &.playing .album-thumb {
    animation: spin 30s linear infinite;

    @media (prefers-reduced-motion) {
      animation: none;
    }
  }
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}
</style>
