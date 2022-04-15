<template>
  <section id="playlistWrapper">
    <screen-header>
      {{ playlist.name }}
      <controls-toggler v-if="playlist.populated" :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span class="meta" v-if="playlist.populated && meta.songCount">
          {{ meta.songCount | pluralize('song') }}
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
        <song-list-controls
          v-if="playlist.populated && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          @deletePlaylist="destroy"
          :songs="playlist.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </screen-header>

    <template v-if="playlist.populated">
      <song-list
        v-if="playlist.songs.length"
        :items="playlist.songs"
        :playlist="playlist"
        type="playlist"
        ref="songList"
      />

      <screen-placeholder v-else>
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
      </screen-placeholder>
    </template>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { pluralize, eventBus } from '@/utils'
import { playlistStore, sharedStore } from '@/stores'
import { download } from '@/services'
import hasSongList from '@/mixins/has-song-list.ts'

export default mixins(hasSongList).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ScreenPlaceholder: () => import('@/components/ui/screen-placeholder.vue')
  },

  filters: { pluralize },

  data: () => ({
    playlist: playlistStore.stub,
    sharedState: sharedStore.state,
    songListControlConfig: {
      deletePlaylist: true
    }
  }),

  created (): void {
    /**
     * Listen to 'main-content-view:load' event to load the requested
     * playlist into view if applicable.
     */
    eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName, playlist: Playlist): void => {
      if (view !== 'Playlist') {
        return
      }

      if (playlist.populated) {
        this.playlist = playlist
        this.state = playlist
      } else {
        this.populate(playlist)
      }
    })
  },

  methods: {
    destroy (): void {
      eventBus.emit('PLAYLIST_DELETE', this.playlist)
    },

    download (): void {
      return download.fromPlaylist(this.playlist)
    },

    editSmartPlaylist (): void {
      eventBus.emit('MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', this.playlist)
    },

    /**
     * Fetch a playlist's content from the server, populate it, and use it afterwards.
     */
    async populate (playlist: Playlist): Promise<void> {
      await playlistStore.fetchSongs(playlist)
      this.playlist = playlist
      this.state = playlist
      this.$nextTick(() => this.$refs.songList && (this.$refs.songList as any).sort())
    }
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
