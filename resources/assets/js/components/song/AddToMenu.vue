<template>
  <div class="add-to" data-testid="add-to-menu" tabindex="0">
    <section class="existing-playlists">
      <p>Add {{ pluralize(songs, 'song') }} to</p>

      <ul v-koel-overflow-fade>
        <template v-if="config.queue">
          <template v-if="queue.length">
            <li
              v-if="currentSong"
              class="queue-after-current"
              data-testid="queue-after-current"
              tabindex="0"
              @click="queueSongsAfterCurrent"
            >
              After Current Song
            </li>
            <li class="bottom-queue" data-testid="queue-bottom" tabindex="0" @click="queueSongsToBottom">
              Bottom of Queue
            </li>
            <li class="top-queue" data-testid="queue-top" tabindex="0" @click="queueSongsToTop">Top of Queue</li>
          </template>
          <li v-else data-testid="queue" tabindex="0" @click="queueSongsToBottom">Queue</li>
        </template>

        <li
          v-if="config.favorites"
          class="favorites"
          data-testid="add-to-favorites"
          tabindex="0"
          @click="addSongsToFavorite"
        >
          Favorites
        </li>

        <li
          v-for="playlist in playlists"
          :key="playlist.id"
          class="playlist"
          data-testid="add-to-playlist"
          tabindex="0"
          @click="addSongsToExistingPlaylist(playlist)"
        >
          {{ playlist.name }}
        </li>
      </ul>
    </section>

    <Btn transparent @click.prevent="addSongsToNewPlaylist">New Playlist…</Btn>
  </div>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { playlistStore, queueStore } from '@/stores'
import { useSongMenuMethods } from '@/composables'

import Btn from '@/components/ui/Btn.vue'

const props = defineProps<{ songs: Song[], config: AddToMenuConfig }>()
const { songs, config } = toRefs(props)

const queue = toRef(queueStore.state, 'songs')
const currentSong = queueStore.current

const allPlaylists = toRef(playlistStore.state, 'playlists')
const playlists = computed(() => allPlaylists.value.filter(({ is_smart }) => !is_smart))

const emit = defineEmits<{ (e: 'closing'): void }>()
const close = () => emit('closing')

const {
  queueSongsAfterCurrent,
  queueSongsToBottom,
  queueSongsToTop,
  addSongsToFavorite,
  addSongsToExistingPlaylist,
  addSongsToNewPlaylist
} = useSongMenuMethods(songs, close)

watch(songs, () => songs.value.length || close())
</script>

<style lang="postcss" scoped>
.add-to {
  width: 100%;
  max-width: 256px;
  min-width: 196px;
  padding: .75rem;

  > * + * {
    margin-top: 1rem;
  }

  p {
    margin-bottom: .5rem;
    font-size: .9rem;
  }

  .new-playlist {
    margin-top: .5rem;
  }

  ul {
    position: relative;
    max-height: 12rem;
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;

    > li + li {
      margin-top: .3rem;
    }
  }

  li {
    height: 2.25rem;
    line-height: 2.25rem;
    padding: 0 .75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-radius: 3px;
    background: rgba(255, 255, 255, .05);
    cursor: pointer;

    &:hover {
      background: var(--color-highlight);
      color: var(--color-text-primary);
    }
  }

  button {
    width: 100%;
    border: 1px solid rgba(255, 255, 255, .2);
  }
}
</style>
