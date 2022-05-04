<template>
  <article
    v-if="showing"
    :class="layout"
    :title="artist.name"
    class="item"
    data-testid="artist-card"
    draggable="true"
    tabindex="0"
    @dblclick="shuffle"
    @dragstart="dragStart"
    @contextmenu.prevent="requestContextMenu"
  >
    <span class="thumbnail-wrapper">
      <ArtistThumbnail :entity="artist" />
    </span>

    <footer>
      <div class="info">
        <a class="name" :href="`#!/artist/${artist.id}`" data-testid="name">{{ artist.name }}</a>
      </div>
      <p class="meta">
        <span class="left">
          {{ pluralize(artist.albums.length, 'album') }}
          •
          {{ pluralize(artist.songs.length, 'song') }}
          •
          {{ duration }}
          •
          {{ pluralize(artist.playCount, 'play') }}
        </span>
        <span class="right">
          <a
            :title="`Shuffle all songs by ${artist.name}`"
            class="shuffle-artist"
            href
            role="button"
            data-testid="shuffle-artist"
            @click.prevent="shuffle"
          >
            <i class="fa fa-random" />
          </a>
          <a
            v-if="allowDownload"
            :title="`Download all songs by ${artist.name}`"
            class="download-artist"
            href
            role="button"
            data-testid="download-artist"
            @click.prevent="download"
          >
            <i class="fa fa-download" />
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRef, toRefs } from 'vue'
import { eventBus, pluralize, startDragging } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'

const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

const props = withDefaults(defineProps<{ artist: Artist, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { artist, layout } = toRefs(props)

const allowDownload = toRef(commonStore.state, 'allowDownload')

const duration = computed(() => songStore.getFormattedLength(artist.value.songs))
const showing = computed(() => artist.value.songs.length && !artistStore.isVariousArtists(artist.value))

const shuffle = () => playbackService.playAllByArtist(artist.value, true /* shuffled */)
const download = () => downloadService.fromArtist(artist.value)
const dragStart = (event: DragEvent) => startDragging(event, artist.value, 'Artist')
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>

<style lang="scss">
@include artist-album-card();
</style>
