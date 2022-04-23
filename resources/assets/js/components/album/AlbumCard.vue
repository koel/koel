<template>
  <article
    v-if="album.songs.length"
    :class="layout"
    :title="`${album.name} by ${album.artist.name}`"
    class="item"
    data-test="album-card"
    draggable="true"
    tabindex="0"
    @dblclick="shuffle"
    @dragstart="dragStart"
    @contextmenu.prevent.stop="requestContextMenu"
  >
    <span class="thumbnail-wrapper">
      <AlbumThumbnail :entity="album"/>
    </span>

    <footer>
      <div class="info">
        <a :href="`#!/album/${album.id}`" class="name">{{ album.name }}</a>
        <span class="sep text-secondary"> by </span>
        <a v-if="isNormalArtist" :href="`#!/artist/${album.artist.id}`" class="artist">{{ album.artist.name }}</a>
        <span class="artist nope" v-else>{{ album.artist.name }}</span>
      </div>
      <p class="meta">
        <span class="left">
          {{ pluralize(album.songs.length, 'song') }}
          •
          {{ duration }}
          •
          {{ pluralize(album.playCount, 'play') }}
        </span>
        <span class="right">
          <a
            :title="`Shuffle all songs in the album ${album.name}`"
            class="shuffle-album"
            href
            role="button"
            @click.prevent="shuffle"
          >
            <i class="fa fa-random"></i>
          </a>
          <a
            v-if="allowDownload"
            :title="`Download all songs in the album ${album.name}`"
            class="download-album"
            href
            role="button"
            @click.prevent="download"
          >
            <i class="fa fa-download"></i>
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRef, toRefs } from 'vue'
import { eventBus, pluralize, startDragging } from '@/utils'
import { artistStore, sharedStore, songStore } from '@/stores'
import { download as downloadService, playback } from '@/services'

const AlbumThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

const props = withDefaults(defineProps<{ album: Album, layout: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { album, layout } = toRefs(props)

const allowDownload = toRef(sharedStore.state, 'allowDownload')

const duration = computed(() => songStore.getFormattedLength(album.value.songs))

const isNormalArtist = computed(() => {
  return !artistStore.isVariousArtists(album.value.artist) && !artistStore.isUnknownArtist(album.value.artist)
})

const shuffle = () => playback.playAllInAlbum(album.value, true /* shuffled */)
const download = () => downloadService.fromAlbum(album.value)
const dragStart = (event: DragEvent) => startDragging(event, album.value, 'Album')
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ALBUM_CONTEXT_MENU_REQUESTED', event, album.value)
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
