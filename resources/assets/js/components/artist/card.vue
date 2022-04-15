<template>
  <article
    :title="artist.name"
    @dragstart="dragStart"
    class="item"
    :class="layout"
    draggable="true"
    tabindex="0"
    data-test="artist-card"
    v-if="showing"
    @contextmenu.prevent="requestContextMenu"
    @dblclick="shuffle"
  >
    <span class="thumbnail-wrapper">
      <artist-thumbnail :entity="artist" />
    </span>

    <footer>
      <div class="info">
        <a class="name" :href="`#!/artist/${artist.id}`">
          {{ artist.name }}
        </a>
      </div>
      <p class="meta">
        <span class="left">
          {{ artist.albums.length | pluralize('album') }}
          •
          {{ artist.songs.length | pluralize('song') }}
          •
          {{ artist.playCount | pluralize('play') }}
        </span>
        <span class="right">
          <a
            :title="`Shuffle all songs by ${artist.name}`"
            @click.prevent="shuffle"
            class="shuffle-artist"
            href
            role="button"
          >
            <i class="fa fa-random"></i>
          </a>
          <a
            :title="`Download all songs by ${artist.name}`"
            @click.prevent="download"
            class="download-artist"
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
import { startDragging, pluralize, eventBus } from '@/utils'
import { artistStore, sharedStore } from '@/stores'
import { playback, download } from '@/services'
import artistAttributes from '@/mixins/artist-attributes.ts'
import { PropOptions } from 'vue'

export default mixins(artistAttributes).extend({
  props: {
    layout: {
      type: String,
      default: 'full'
    } as PropOptions<ArtistAlbumCardLayout>
  },

  components: {
    ArtistThumbnail: () => import('@/components/ui/album-artist-thumbnail.vue')
  },

  filters: { pluralize },

  data: () => ({
    sharedState: sharedStore.state
  }),

  computed: {
    showing (): boolean {
      return Boolean(this.artist.songs.length && !artistStore.isVariousArtists(this.artist))
    }
  },

  methods: {
    shuffle (): void {
      playback.playAllByArtist(this.artist, true /* shuffled */)
    },

    download (): void {
      download.fromArtist(this.artist)
    },

    dragStart (event: DragEvent): void {
      startDragging(event, this.artist, 'Artist')
    },

    requestContextMenu (e: MouseEvent): void {
      eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', e, this.artist)
    }
  }
})
</script>

<style lang="scss">
@include artist-album-card();
</style>
