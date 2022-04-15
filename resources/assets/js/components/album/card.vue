<template>
  <article
    :title="`${album.name} by ${album.artist.name}`"
    @dragstart="dragStart"
    class="item"
    :class="layout"
    draggable="true"
    tabindex="0"
    data-test="album-card"
    v-if="album.songs.length"
    @contextmenu.prevent="requestContextMenu"
    @dblclick="shuffle"
  >
    <span class="thumbnail-wrapper">
      <album-thumbnail :entity="album" />
    </span>

    <footer>
      <div class="info">
        <a class="name" :href="`#!/album/${album.id}`">{{ album.name }}</a>
        <span class="sep text-secondary">by</span>
        <a
          class="artist"
          v-if="isNormalArtist"
          :href="`#!/artist/${album.artist.id}`"
        >{{ album.artist.name }}</a>
        <span class="artist nope" v-else>{{ album.artist.name }}</span>
      </div>
      <p class="meta">
        <span class="left">
          {{ album.songs.length | pluralize('song') }}
          •
          {{ fmtLength }}
          •
          {{ album.playCount | pluralize('play') }}
        </span>
        <span class="right">
          <a
            :title="`Shuffle all songs in the album ${album.name}`"
            @click.prevent="shuffle"
            class="shuffle-album"
            href
            role="button"
          >
            <i class="fa fa-random"></i>
          </a>
          <a
            :title="`Download all songs in the album ${album.name}`"
            @click.prevent="download"
            class="download-album"
            href
            role="button"
            v-if="sharedState.allowDownload"
          >
            <i class="fa fa-download"></i>
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { eventBus, pluralize, startDragging } from '@/utils'
import { artistStore, sharedStore } from '@/stores'
import { playback, download } from '@/services'
import albumAttributes from '@/mixins/album-attributes.ts'
import { PropOptions } from 'vue'

export default mixins(albumAttributes).extend({
  props: {
    layout: {
      type: String,
      default: 'full'
    } as PropOptions<ArtistAlbumCardLayout>
  },

  components: {
    AlbumThumbnail: () => import('@/components/ui/album-artist-thumbnail.vue')
  },

  filters: { pluralize },

  data: () => ({
    sharedState: sharedStore.state
  }),

  computed: {
    isNormalArtist (): boolean {
      return !artistStore.isVariousArtists(this.album.artist) &&
        !artistStore.isUnknownArtist(this.album.artist)
    }
  },

  methods: {
    shuffle (): void {
      playback.playAllInAlbum(this.album, true /* shuffled */)
    },

    download (): void {
      download.fromAlbum(this.album)
    },

    dragStart (event: DragEvent): void {
      startDragging(event, this.album, 'Album')
    },

    requestContextMenu (e: MouseEvent): void {
      eventBus.emit('ALBUM_CONTEXT_MENU_REQUESTED', e, this.album)
    }
  }
})
</script>

<style lang="scss">
.sep {
  display: none;

  .as-list & {
    display: inline;
  }
}

@include artist-album-card();
</style>
