<template>
  <section id="playlistWrapper" v-if="playlist">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      {{ playlist.name }}
      <ControlsToggle v-if="songs.length" v-model="showingControls"/>

      <template v-slot:thumbnail>
        <ThumbnailStack :thumbnails="thumbnails"/>
      </template>

      <template v-slot:meta v-if="songs.length">
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>
        <a
          v-if="allowDownload"
          href
          role="button"
          title="Download all songs in playlist"
          @click.prevent="download"
        >
          Download All
        </a>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="!isPhone || showingControls"
          :config="controlsConfig"
          @deletePlaylist="destroy"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongListSkeleton v-if="loading"/>
    <SongList
      v-if="!loading && songs.length"
      ref="songList"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
      @sort="sort"
    />

    <ScreenEmptyState v-if="!songs.length && !loading">
      <template v-slot:icon>
        <icon :icon="faFile"/>
      </template>

      <template v-if="playlist?.is_smart">
        No songs match the playlist's
        <a @click.prevent="editPlaylist">criteria</a>.
      </template>
      <template v-else>
        The playlist is currently empty.
        <span class="d-block secondary">
          Drag songs into its name in the sidebar or use the &quot;Add To…&quot; button to fill it up.
        </span>
      </template>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { faFile } from '@fortawesome/free-regular-svg-icons'
import { differenceBy } from 'lodash'
import { ref, toRef } from 'vue'
import { eventBus, pluralize, requireInjection } from '@/utils'
import { commonStore, playlistStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList } from '@/composables'
import { MessageToasterKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const toaster = requireInjection(MessageToasterKey)
const playlist = ref<Playlist>()
const loading = ref(false)

const controlsConfig: Partial<SongListControlsConfig> = { deletePlaylist: true }

const {
  SongList,
  SongListControls,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  duration,
  thumbnails,
  selectedSongs,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  onScrollBreakpoint,
  sort
} = useSongList(ref<Song[]>([]), 'playlist')

const allowDownload = toRef(commonStore.state, 'allow_download')

const destroy = () => eventBus.emit('PLAYLIST_DELETE', playlist.value)
const download = () => downloadService.fromPlaylist(playlist.value!)
const editPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value)

const removeSelected = () => {
  if (!selectedSongs.value.length || playlist.value!.is_smart) return

  playlistStore.removeSongs(playlist.value!, selectedSongs.value)
  songs.value = differenceBy(songs.value, selectedSongs.value, 'id')
  toaster.value.success(`Removed ${pluralize(selectedSongs.value, 'song')} from "${playlist.value!.name}."`)
}

const fetchSongs = async () => {
  loading.value = true
  songs.value = await songStore.fetchForPlaylist(playlist.value!)
  loading.value = false
  sort()
}

eventBus.on({
    'LOAD_MAIN_CONTENT': async (view: MainViewName, p: any) => {
      if (view === 'Playlist') {
        playlist.value = p as Playlist
        await fetchSongs()
      }
    },

    'SMART_PLAYLIST_UPDATED': async (updated: Playlist) => updated === playlist.value && await fetchSongs()
  }
)
</script>
