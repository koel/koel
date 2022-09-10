<template>
  <div
    v-show="showing"
    v-koel-clickaway="close"
    v-koel-focus
    class="add-to"
    data-testid="add-to-menu"
    tabindex="0"
    @keydown.esc="close"
  >
    <section class="existing-playlists">
      <p>Add {{ pluralize(songs, 'song') }} to</p>

      <ul>
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

        <template v-if="config.playlists">
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
        </template>
      </ul>
    </section>

    <section v-if="config.newPlaylist" class="new-playlist" data-testid="new-playlist">
      <p>or create a new playlist</p>

      <form class="form-save form-simple form-new-playlist" @submit.prevent="createNewPlaylistFromSongs">
        <input
          v-model="newPlaylistName"
          data-testid="new-playlist-name"
          placeholder="Playlist name"
          required
          type="text"
          @keyup.esc.prevent="close"
        >
        <Btn title="Save" type="submit">⏎</Btn>
      </form>
    </section>
  </div>
</template>

<script lang="ts" setup>
import { computed, nextTick, ref, toRef, toRefs, watch } from 'vue'
import { pluralize, requireInjection } from '@/utils'
import { playlistStore, queueStore } from '@/stores'
import { useSongMenuMethods } from '@/composables'
import router from '@/router'
import { MessageToasterKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'

const toaster = requireInjection(MessageToasterKey)
const props = defineProps<{ songs: Song[], showing: Boolean, config: AddToMenuConfig }>()
const { songs, showing, config } = toRefs(props)

const newPlaylistName = ref('')
const queue = toRef(queueStore.state, 'songs')
const currentSong = toRef(queueStore.state, 'current')

const allPlaylists = toRef(playlistStore.state, 'playlists')
const playlists = computed(() => allPlaylists.value.filter(playlist => !playlist.is_smart))

const emit = defineEmits(['closing'])
const close = () => emit('closing')

const {
  queueSongsAfterCurrent,
  queueSongsToBottom,
  queueSongsToTop,
  addSongsToFavorite,
  addSongsToExistingPlaylist
} = useSongMenuMethods(songs, close)

watch(songs, () => songs.value.length || close())

/**
 * Save the selected songs as a playlist.
 * As of current we don't have selective save.
 */
const createNewPlaylistFromSongs = async () => {
  newPlaylistName.value = newPlaylistName.value.trim()

  if (!newPlaylistName.value) {
    return
  }

  const playlist = await playlistStore.store(newPlaylistName.value, songs.value)
  newPlaylistName.value = ''

  toaster.value.success(`Playlist "${playlist.name}" created.`)

  // Activate the new playlist right away
  await nextTick()
  router.go(`playlist/${playlist.id}`)

  close()
}
</script>

<style lang="scss" scoped>
.add-to {
  @include context-menu();

  width: 100%;
  max-width: 225px;
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

  &::before {
    display: block;
    content: " ";
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid var(--color-bg-secondary);
    position: absolute;
    top: -7px;
    left: calc(50% - 10px);
  }

  form {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;

    input[type="text"] {
      width: 100%;
      border-radius: 5px 0 0 5px;
      height: 28px;
    }

    button[type="submit"] {
      margin-top: 0;
      border-radius: 0 5px 5px 0 !important;
      height: 28px;
      line-height: 28px;
      padding-top: 0;
      padding-bottom: 0;
      margin-left: -2px !important;
    }
  }
}
</style>
