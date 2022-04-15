<template>
  <section id="artistWrapper">
    <screen-header>
      {{ artist.name }}
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <artist-thumbnail :entity="artist"/>
      </template>

      <template v-slot:meta>
        <span v-if="artist.songs.length">
          {{ artist.albums.length | pluralize('album') }}
          •
          {{ artist.songs.length | pluralize('song') }}
          •
          {{ fmtLength }}

          <template v-if="sharedState.useLastfm">
            •
            <a class="info" href @click.prevent="showInfo" title="View artist's extra information">Info</a>
          </template>

          <template v-if="sharedState.allowDownload">
            •
            <a
              @click.prevent="download"
              class="download"
              href
              role="button"
              title="Download all songs by this artist"
            >
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <song-list-controls
          v-if="artist.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="artist.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </screen-header>

    <song-list :items="artist.songs" type="artist" :config="listConfig" ref="songList"/>

    <section class="info-wrapper" v-if="sharedState.useLastfm && meta.showing">
      <close-modal-btn @click="meta.showing = false"/>
      <div class="inner">
        <div class="loading" v-if="meta.loading">
          <sound-bar/>
        </div>
        <artist-info :artist="artist" mode="full" v-else/>
      </div>
    </section>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { pluralize } from '@/utils'
import { sharedStore } from '@/stores'
import { download, artistInfo as artistInfoService } from '@/services'
import router from '@/router'
import hasSongList from '@/mixins/has-song-list.ts'
import artistAttributes from '@/mixins/artist-attributes.ts'
import { SongListConfig } from '@/components/song/list.vue'

export default mixins(hasSongList, artistAttributes).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ArtistInfo: () => import('@/components/artist/info.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    ArtistThumbnail: () => import('@/components/ui/album-artist-thumbnail.vue'),
    CloseModalBtn: () => import('@/components/ui/close-modal-btn.vue')
  },

  filters: { pluralize },

  data: () => ({
    listConfig: {
      columns: ['track', 'title', 'album', 'length']
    } as Partial<SongListConfig>,

    sharedState: sharedStore.state,

    meta: {
      showing: false,
      loading: true
    }
  }),

  watch: {
    /**
     * Watch the artist's album count.
     * If this is changed to 0, the user has edit the songs by this artist
     * and move all of them to another artist (thus delete this artist entirely).
     * We should then go back to the artist list.
     */
    'artist.albums.length': (newAlbumCount: number): void => {
      if (!newAlbumCount) {
        router.go('artists')
      }
    },

    artist (): void {
      this.meta.showing = false
      // #530
      if (this.$refs.songList) {
        (this.$refs.songList as any).sort()
      }
    }
  },

  methods: {
    download (): void {
      download.fromArtist(this.artist)
    },

    async showInfo (): Promise<void> {
      this.meta.showing = true

      if (!this.artist.info) {
        try {
          await artistInfoService.fetch(this.artist)
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
#artistWrapper {
  @include artist-album-info-wrapper();
}
</style>
