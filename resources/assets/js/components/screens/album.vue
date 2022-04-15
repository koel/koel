<template>
  <section id="albumWrapper">
    <screen-header>
      {{ album.name }}
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <album-thumbnail :entity="album"/>
      </template>

      <template v-slot:meta>
        <span v-if="album.songs.length">
          by
          <a class="artist" v-if="isNormalArtist" :href="`#!/artist/${album.artist.id}`">{{ album.artist.name }}</a>
          <span class="nope" v-else>{{ album.artist.name }}</span>
          •
          {{ album.songs.length | pluralize('song') }}
          •
          {{ fmtLength }}

          <template v-if="sharedState.useLastfm">
            •
            <a class="info" href @click.prevent="showInfo" title="View album's extra information">Info</a>
          </template>
          <template v-if="sharedState.allowDownload">
            •
            <a class="download" href @click.prevent="download" title="Download all songs in album" role="button">
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <song-list-controls
          v-if="album.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="album.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </screen-header>

    <song-list :items="album.songs" type="album" :config="listConfig" ref="songList"/>

    <section class="info-wrapper" v-if="sharedState.useLastfm && meta.showing">
      <close-modal-btn @click="meta.showing = false"/>
      <div class="inner">
        <div class="loading" v-if="meta.loading">
          <sound-bar/>
        </div>
        <album-info :album="album" mode="full" v-else/>
      </div>
    </section>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { pluralize } from '@/utils'
import { artistStore, sharedStore } from '@/stores'
import { download, albumInfo as albumInfoService } from '@/services'
import router from '@/router'
import hasSongList from '@/mixins/has-song-list.ts'
import albumAttributes from '@/mixins/album-attributes.ts'
import { SongListConfig } from '@/components/song/list.vue'

export default mixins(hasSongList, albumAttributes).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    AlbumInfo: () => import('@/components/album/info.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    AlbumThumbnail: () => import('@/components/ui/album-artist-thumbnail.vue'),
    CloseModalBtn: () => import('@/components/ui/close-modal-btn.vue')
  },

  filters: { pluralize },

  data: () => ({
    sharedState: sharedStore.state,

    listConfig: {
      columns: ['track', 'title', 'length']
    } as Partial<SongListConfig>,

    meta: {
      showing: false,
      loading: true
    }
  }),

  computed: {
    isNormalArtist (): boolean {
      return !artistStore.isVariousArtists(this.album.artist) &&
        !artistStore.isUnknownArtist(this.album.artist)
    }
  },

  watch: {
    /**
     * Watch the album's song count.
     * If this is changed to 0, the user has edit the songs in this album
     * and move all of them into another album.
     * We should then go back to the album list.
     */
    'album.songs.length': (newSongCount: number): void => {
      if (!newSongCount) {
        router.go('albums')
      }
    },

    album (): void {
      this.meta.showing = false
      // #530
      if (this.$refs.songList) {
        (this.$refs.songList as any).sort()
      }
    }
  },

  methods: {
    download (): void {
      download.fromAlbum(this.album)
    },

    async showInfo (): Promise<void> {
      this.meta.showing = true

      if (!this.album.info) {
        try {
          await albumInfoService.fetch(this.album)
        } catch (e) {
          /* eslint no-console: 0 */
          console.error(e)
        } finally {
          this.meta.loading = false
        }
      } else {
        this.meta.loading = false
      }
    }
  }
})
</script>

<style lang="scss" scoped>
#albumWrapper {
  @include artist-album-info-wrapper();
}
</style>
