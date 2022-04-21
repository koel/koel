<template>
  <div
    class="add-to"
    v-show="showing"
    tabindex="0"
    v-koel-clickaway="close"
    v-koel-focus
    @keydown.esc="close"
    data-test="add-to-menu"
  >
    <section class="existing-playlists">
      <p>Add {{ pluralize(songs.length, 'song') }} to</p>

      <ul>
        <template v-if="config.queue">
          <li class="after-current" role="button" tabindex="0" @click="queueSongsAfterCurrent">After Current Song</li>
          <li class="bottom-queue" role="button" tabindex="0" @click="queueSongsToBottom">Bottom of Queue</li>
          <li class="top-queue" role="button" tabindex="0" @click="queueSongsToTop">Top of Queue</li>
        </template>

        <li
          @click="addSongsToFavorite"
          class="favorites"
          tabindex="0"
          v-if="config.favorites"
        >
          Favorites
        </li>

        <template v-if="config.playlists">
          <li
            :key="playlist.id"
            @click="addSongsToExistingPlaylist(playlist)"
            class="playlist"
            tabindex="0"
            v-for="playlist in playlists"
          >
            {{ playlist.name }}
          </li>
        </template>
      </ul>
    </section>

    <section class="new-playlist" v-if="config.newPlaylist">
      <p>or create a new playlist</p>

      <form class="form-save form-simple form-new-playlist" @submit.prevent="createNewPlaylistFromSongs">
        <input
          @keyup.esc.prevent="close"
          required
          type="text"
          placeholder="Playlist name"
          v-model="newPlaylistName"
          data-test="new-playlist-name"
        >
        <Btn type="submit" title="Save">⏎</Btn>
      </form>
    </section>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, reactive, ref, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { playlistStore } from '@/stores'
import router from '@/router'
import { useSongMenuMethods } from '@/composables'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const props = defineProps<{ songs: Song[], showing: Boolean, config: AddToMenuConfig }>()
const { songs, showing, config } = toRefs(props)

const newPlaylistName = ref('')
const playlistState = reactive(playlistStore.state)

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

const playlists = computed(() => playlistState.playlists.filter(playlist => !playlist.is_smart))

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
