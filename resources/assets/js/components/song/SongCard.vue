<template>
  <article
    :class="{ playing: song.playback_state === 'Playing' || song.playback_state === 'Paused' }"
    draggable="true"
    tabindex="0"
    @dragstart="onDragStart"
    @contextmenu.prevent="requestContextMenu"
    @dblclick.prevent="play"
  >
    <SongThumbnail :song="song" />
    <main>
      <div class="details">
        <h3>
          <span v-if="external" class="external-mark">
            <Icon :icon="faSquareUpRight" />
          </span>
          {{ song.title }}
        </h3>
        <p class="by text-secondary">
          <a :href="`#/artist/${song.artist_id}`">{{ song.artist_name }}</a>
          - {{ pluralize(song.play_count, 'play') }}
        </p>
      </div>
      <LikeButton :song="song" />
    </main>
  </article>
</template>

<script lang="ts" setup>
import {faSquareUpRight} from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { eventBus, pluralize } from '@/utils'
import { queueStore } from '@/stores'
import { playbackService } from '@/services'
import { useAuthorization, useDraggable, useKoelPlus } from '@/composables'

import SongThumbnail from '@/components/song/SongThumbnail.vue'
import LikeButton from '@/components/song/SongLikeButton.vue'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const { isPlus } = useKoelPlus()
const { currentUser } = useAuthorization()
const { startDragging } = useDraggable('songs')

const external = computed(() => isPlus.value && song.value.owner_id !== currentUser.value?.id)

const requestContextMenu = (event: MouseEvent) => eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
const onDragStart = (event: DragEvent) => startDragging(event, [song.value])

const play = () => {
  queueStore.queueIfNotQueued(song.value)
  playbackService.play(song.value)
}
</script>

<style lang="scss" scoped>
article {
  display: flex;
  gap: 12px;
  padding: 8px 12px 8px 8px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-bg-secondary);
  border-radius: 5px;
  align-items: center;
  transition: border-color .2s ease-in-out;

  &:focus, &:focus-within {
    box-shadow: 0 0 1px 1px var(--color-accent);
  }

  &.playing {
    color: var(--color-accent);
  }

  button {
    color: var(--color-text-secondary);
    opacity: 0;
  }

  &:hover {
    border-color: rgba(255, 255, 255, .15);

    button {
      opacity: 1;
    }
  }

  @media (hover: none) {
    button {
      opacity: 1;
    }

    :deep(.cover) {
      .control {
        display: flex;
      }

      &::before {
        opacity: .7;
      }
    }
  }

  // show the thumbnail's playback control on the whole card focus and hover
  &:hover :deep(.cover), &:focus :deep(.cover) {
    .control {
      display: flex;
    }

    &::before {
      opacity: .7;
    }
  }

  main {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;

    .by {
      font-size: .9rem;
      opacity: .8;

      a {
        color: var(--color-text-primary);

        &:hover {
          color: var(--color-accent);
        }
      }
    }

    .details {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
      overflow: hidden;

      .external-mark {
        margin-right: .2rem;
        opacity: .5;
      }
    }
  }

  h3 {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
  }
}
</style>
