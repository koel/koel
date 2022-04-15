<template>
  <section id="playlistWrapper">
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
        v-if="playlist.songs.length"
        :items="playlist.songs"
        :playlist="playlist"
        type="playlist"
        ref="songList"
      />

      <ScreenPlaceholder v-else>
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
      </ScreenPlaceholder>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils'
import { playlistStore, sharedStore } from '@/stores'
import { download as downloadService } from '@/services'
import { useSongList } from '@/composables'
import { defineAsyncComponent, nextTick, reactive, ref } from 'vue'
import { pluralize } from '@/utils'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ScreenPlaceholder = defineAsyncComponent(() => import('@/components/ui/screen-placeholder.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  meta,
  state,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  playAll,
  playSelected,
  toggleControls
} = useSongList({
  deletePlaylist: true
})

const playlist = ref<Playlist>(playlistStore.stub)
const sharedState = reactive(sharedStore.state)

const destroy = () => eventBus.emit('PLAYLIST_DELETE', playlist.value)
const download = () => downloadService.fromPlaylist(playlist.value)
const editSmartPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', playlist.value)

/**
 * Fetch a playlist's content from the server, populate it, and use it afterwards.
 */
const populate = async (_playlist: Playlist) => {
  await playlistStore.fetchSongs(_playlist)
  playlist.value = _playlist
  state.songs = playlist.value.songs
  await nextTick()
  // @ts-ignore
  songList.value?.sort()
}

eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName, _playlist: Playlist): void => {
  if (view !== 'Playlist') {
    return
  }

  if (_playlist.populated) {
    playlist.value = _playlist
    state.songs = playlist.value.songs
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
