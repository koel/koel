<template>
  <section id="playlistWrapper" v-if="playlist">
    <ScreenHeader>
      {{ playlist.name }}
      <ControlsToggler v-if="playlist.populated" :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span class="meta" v-if="playlist.populated && meta.songCount">
          {{ pluralize(meta.songCount, 'song') }}
          •
          {{ meta.totalLength }}
          <template v-if="sharedState.allowDownload && playlist.songs.length">
            •
            <a href @click.prevent="download" title="Download all songs in playlist" role="button">
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="playlist.populated && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          @deletePlaylist="destroy"
          :songs="playlist.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <template v-if="playlist.populated">
      <SongList
        v-if="songs.length"
        ref="songList"
        :items="songs"
        type="playlist"
        @press:delete="removeSelected"
        @press:enter="onPressEnter"
      />

      <ScreenEmptyState v-else>
        <template v-slot:icon>
          <i class="fa fa-file-o"></i>
        </template>

        <template v-if="playlist.is_smart">
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
    </template>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, nextTick, ref, watch } from 'vue'
import { eventBus, pluralize } from '@/utils'
import { playlistStore, sharedStore } from '@/stores'
import { download as downloadService } from '@/services'
import { useSongList } from '@/composables'
import { difference } from 'lodash'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))

const playlist = ref<Playlist>()

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songs,
  songList,
  meta,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(ref(playlist.value?.songs || []), { deletePlaylist: true })

const sharedState = sharedStore.state

const destroy = () => eventBus.emit('PLAYLIST_DELETE', playlist.value)
const download = () => downloadService.fromPlaylist(playlist.value!)
const editSmartPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', playlist.value)

const removeSelected = () => {
  if (!selectedSongs.value.length) return

  playlistStore.removeSongs(playlist.value!, selectedSongs.value)
  songs.value = difference(songs.value, selectedSongs.value)
}

/**
 * Fetch a playlist's content from the server, populate it, and use it afterwards.
 */
const populate = async (_playlist: Playlist) => {
  await playlistStore.fetchSongs(_playlist)
  playlist.value = _playlist
  songs.value = playlist.value.songs
  await nextTick()
  songList.value?.sort()
}

eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName, _playlist: Playlist): void => {
  if (view !== 'Playlist') {
    return
  }

  if (_playlist.populated) {
    playlist.value = _playlist
    songs.value = playlist.value.songs
  } else {
    populate(_playlist)
  }
})
</script>

<style lang="scss">
#playlistWrapper {
  .none {
    padding: 16px 24px;
  }
}
</style>
