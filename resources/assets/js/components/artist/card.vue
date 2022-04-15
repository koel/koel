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
    @contextmenu.prevent.stop="requestContextMenu"
    @dblclick="shuffle"
  >
    <span class="thumbnail-wrapper">
      <ArtistThumbnail :entity="artist"/>
    </span>

    <footer>
      <div class="info">
        <a class="name" :href="`#!/artist/${artist.id}`">
          {{ artist.name }}
        </a>
      </div>
      <p class="meta">
        <span class="left">
          {{ pluralize(artist.albums.length, 'album') }}
          •
          {{ pluralize(artist.songs.length, 'song') }}
          •
          {{ pluralize(artist.playCount, 'play') }}
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

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive, toRefs } from 'vue'
import { eventBus, pluralize, startDragging } from '@/utils'
import { artistStore, sharedStore } from '@/stores'
import { download as downloadService, playback } from '@/services'
import { useArtistAttributes } from '@/composables'

const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/album-artist-thumbnail.vue'))

const props = withDefaults(defineProps<{ artist: Artist, layout: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { artist, layout } = toRefs(props)

const { length, fmtLength, image } = useArtistAttributes(artist.value)

const sharedState = reactive(sharedStore.state)

const showing = computed(() => artist.value.songs.length && !artistStore.isVariousArtists(artist.value))

const shuffle = () => playback.playAllByArtist(artist.value, true /* shuffled */)
const download = () => downloadService.fromArtist(artist.value)
const dragStart = (event: DragEvent) => startDragging(event, artist.value, 'Artist')
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>

<style lang="scss">
@include artist-album-card();
</style>
