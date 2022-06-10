<template>
  <section id="playlistWrapper" v-if="playlist">
    <ScreenHeader>
      {{ playlist?.name }}
      <ControlsToggle v-if="songs.length" :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span class="meta" v-if="songs.length">
          {{ pluralize(songs.length, 'song') }}
          •
          {{ duration }}
          <template v-if="allowDownload">
            •
            <a href role="button" title="Download all songs in playlist" @click.prevent="download">Download All</a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="!isPhone || showingControls"
          @playAll="playAll"
          @playSelected="playSelected"
          @deletePlaylist="destroy"
          :config="controlsConfig"
        />
      </template>
    </ScreenHeader>

    <SongList
      v-if="songs.length"
      ref="songList"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @sort="sort"
    />

    <ScreenEmptyState v-if="!songs.length && !loading">
      <template v-slot:icon>
        <i class="fa fa-file-o"></i>
      </template>

      <template v-if="playlist?.is_smart">
        No songs match the playlist's
        <a @click.prevent="editSmartPlaylist">criteria</a>.
      </template>
      <template v-else>
        The playlist is currently empty.
        <span class="d-block secondary">
          Drag songs into its name in the sidebar
          or use the &quot;Add To…&quot; button to fill it up.
        </span>
      </template>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { difference } from 'lodash'
import { defineAsyncComponent, nextTick, ref, toRef } from 'vue'
import { alerts, eventBus, pluralize } from '@/utils'
import { commonStore, playlistStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList } from '@/composables'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))

const playlist = ref<Playlist>()
const playlistSongs = ref<Song[]>([])
const loading = ref(false)

const controlsConfig: Partial<SongListControlsConfig> = { deletePlaylist: true }

const {
  SongList,
  SongListControls,
  ControlsToggle,
  songs,
  songList,
  duration,
  selectedSongs,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls,
  sort
} = useSongList(playlistSongs, 'playlist')

const allowDownload = toRef(commonStore.state, 'allow_download')

const destroy = () => eventBus.emit('PLAYLIST_DELETE', playlist.value)
const download = () => downloadService.fromPlaylist(playlist.value!)
const editSmartPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', playlist.value)

const removeSelected = () => {
  if (!selectedSongs.value.length) return

  playlistStore.removeSongs(playlist.value!, selectedSongs.value)
  songs.value = difference(songs.value, selectedSongs.value)
  alerts.success(`Removed ${pluralize(selectedSongs.value.length, 'song')} from "${playlist.value!.name}."`)
}

const fetchSongs = async () => {
  loading.value = true
  playlistSongs.value = await songStore.fetchForPlaylist(playlist.value!)
  loading.value = false
  await nextTick()
  sort('title', 'asc')
}

eventBus.on({
    LOAD_MAIN_CONTENT (view: MainViewName, playlistFromRoute: Playlist) {
      if (view !== 'Playlist') {
        return
      }

      playlistSongs.value = []
      playlist.value = playlistFromRoute
      fetchSongs()
    },

    'SMART_PLAYLIST_UPDATED': (updated: Playlist) => updated === playlist.value && fetchSongs()
  }
)
</script>

<style lang="scss">
#playlistWrapper {
  .none {
    padding: 16px 24px;
  }
}
</style>
