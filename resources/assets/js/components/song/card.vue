<template>
  <article
    :class="{ playing: song.playbackState === 'Playing' || song.playbackState === 'Paused' }"
    @contextmenu.stop.prevent="requestContextMenu"
    @dblclick.prevent="play"
    @dragstart="dragStart"
    draggable="true"
    data-test="song-card"
    tabindex="0"
  >
    <span class="cover" :style="{ backgroundImage: `url(${song.album.cover})` }">
      <a class="control" @click.prevent="changeSongState">
        <i class="fa fa-play" v-if="song.playbackState !== 'Playing'"/>
        <i class="fa fa-pause" v-else/>
      </a>
    </span>
    <span class="main">
      <span class="details">
        <span v-if="showPlayCount" :style="{ width: `${song.playCount*100/topPlayCount}%` }" class="play-count"/>
        {{ song.title }}
        <span class="by text-secondary">
          <a :href="`#!/artist/${song.artist.id}`">{{ song.artist.name }}</a>
          <template v-if="showPlayCount"> - {{ pluralize(song.playCount, 'play') }}</template>
        </span>
      </span>
      <span class="favorite">
        <LikeButton :song="song"/>
      </span>
    </span>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { eventBus, pluralize, startDragging } from '@/utils'
import { queueStore } from '@/stores'
import { playback } from '@/services'

const LikeButton = defineAsyncComponent(() => import('@/components/song/SongLikeButton.vue'))

const props = withDefaults(defineProps<{ song: Song, topPlayCount?: number }>(), { topPlayCount: 0 })

const { song, topPlayCount } = toRefs(props)

const showPlayCount = computed(() => Boolean(topPlayCount && song.value.playCount))

const requestContextMenu = (event: MouseEvent) => eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
const dragStart = (event: DragEvent) => startDragging(event, song.value, 'Song')

const play = () => {
  queueStore.contains(song.value) || queueStore.queueAfterCurrent(song.value)
  playback.play(song.value)
}

const changeSongState = () => {
  if (song.value.playbackState === 'Stopped') {
    play()
  } else if (song.value.playbackState === 'Paused') {
    playback.resume()
  } else {
    playback.pause()
  }
}
</script>

<style lang="scss" scoped>
article {
  display: flex;

  &.playing {
    color: var(--color-highlight);
  }

  &:hover .cover, &:focus .cover {
    .control {
      display: block;
    }

    &::before {
      opacity: .7;
    }
  }

  .cover {
    flex: 0 0 48px;
    height: 48px;
    background-size: cover;
    position: relative;

    @include vertical-center();

    &::before {
      content: " ";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      background: #000;
      opacity: 0;

      @media (hover: none) {
        opacity: .7;
      }
    }

    .control {
      border-radius: 50%;
      width: 28px;
      height: 28px;
      background: rgba(0, 0, 0, .7);
      line-height: 2rem;
      font-size: 1rem;
      text-align: center;
      z-index: 1;
      display: none;
      color: var(--color-text-primary);
      transition: .3s;

      @media (hover: none) {
        display: block;
      }
    }
  }

  .main {
    flex: 1;
    padding: 4px 8px;
    position: relative;
    display: flex;

    .play-count {
      background: rgba(255, 255, 255, 0.08);
      position: absolute;
      height: 100%;
      top: 0;
      left: 0;
      pointer-events: none;
    }

    .by {
      display: block;
      font-size: .9rem;
      margin-top: 2px;
      opacity: .8;

      a {
        color: var(--color-text-primary);

        &:hover {
          color: var(--color-highlight);
        }
      }
    }

    .details {
      flex: 1;
    }
  }
}
</style>
