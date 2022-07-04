<template>
  <article
    :class="{ playing: song.playback_state === 'Playing' || song.playback_state === 'Paused' }"
    data-testid="song-card"
    draggable="true"
    tabindex="0"
    @dragstart="dragStart"
    @contextmenu.prevent="requestContextMenu"
    @dblclick.prevent="play"
  >
    <span :style="{ backgroundImage: `url(${song.album_cover})` }" class="cover">
      <a class="control" @click.prevent="changeSongState" data-testid="play-control">
        <i v-if="song.playback_state !== 'Playing'" class="fa fa-play"/>
        <i class="fa fa-pause" v-else/>
      </a>
    </span>
    <span class="main">
      <span class="details">
        <span v-if="showPlayCount" :style="{ width: `${song.play_count*100/topPlayCount}%` }" class="play-count"/>
        {{ song.title }}
        <span class="by text-secondary">
          <a :href="`#!/artist/${song.artist_id}`">{{ song.artist_name }}</a>
          <template v-if="showPlayCount"> - {{ pluralize(song.play_count, 'play') }}</template>
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
import { playbackService } from '@/services'

const LikeButton = defineAsyncComponent(() => import('@/components/song/SongLikeButton.vue'))

const props = withDefaults(defineProps<{ song: Song, topPlayCount?: number }>(), { topPlayCount: 0 })
const { song, topPlayCount } = toRefs(props)

const showPlayCount = computed(() => Boolean(topPlayCount && song.value.play_count))

const requestContextMenu = (event: MouseEvent) => eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
const dragStart = (event: DragEvent) => startDragging(event, song.value, 'Song')

const play = () => {
  queueStore.queueIfNotQueued(song.value)
  playbackService.play(song.value)
}

const changeSongState = () => {
  if (song.value.playback_state === 'Stopped') {
    play()
  } else if (song.value.playback_state === 'Paused') {
    playbackService.resume()
  } else {
    playbackService.pause()
  }
}
</script>

<style lang="scss" scoped>
article {
  display: flex;
  padding: 8px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-bg-secondary);
  border-radius: 5px;
  align-items: center;

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
    border-radius: 4px;

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
    position: relative;
    padding: 0 12px;
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
