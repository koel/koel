<template>
  <article
    v-if="showing"
    :class="layout"
    :title="`${album.name} by ${album.artist_name}`"
    class="item"
    data-testid="album-card"
    draggable="true"
    tabindex="0"
    @dblclick="shuffle"
    @dragstart="dragStart"
    @contextmenu.prevent="requestContextMenu"
  >
    <span class="thumbnail-wrapper" :style="{ backgroundImage: `url(${defaultCover})` }">
      <AlbumThumbnail :entity="album"/>
    </span>

    <footer>
      <div class="info">
        <a :href="`#!/album/${album.id}`" class="name" data-testid="name">{{ album.name }}</a>
        <span class="sep text-secondary"> by </span>
        <a v-if="isStandardArtist" :href="`#!/artist/${album.artist_id}`" class="artist">{{ album.artist_name }}</a>
        <span v-else class="text-secondary">{{ album.artist_name }}</span>
      </div>
      <p class="meta">
        <span class="left">
          {{ pluralize(album.song_count, 'song') }}
          •
          {{ duration }}
          •
          {{ pluralize(album.play_count, 'play') }}
        </span>
        <span class="right">
          <a
            :title="`Shuffle all songs in the album ${album.name}`"
            class="shuffle-album"
            data-testid="shuffle-album"
            href
            role="button"
            @click.prevent="shuffle"
          >
            <i class="fa fa-random"/>
          </a>
          <a
            v-if="allowDownload"
            :title="`Download all songs in the album ${album.name}`"
            class="download-album"
            data-testid="download-album"
            href
            role="button"
            @click.prevent="download"
          >
            <i class="fa fa-download"/>
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRef, toRefs } from 'vue'
import { defaultCover, eventBus, pluralize, secondsToHis, startDragging } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'

const AlbumThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

const props = withDefaults(defineProps<{ album: Album, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { album, layout } = toRefs(props)

const allowDownload = toRef(commonStore.state, 'allow_download')

const duration = computed(() => secondsToHis(album.value.length))
const isStandardArtist = computed(() => artistStore.isStandard(album.value.artist_id))
const showing = computed(() => !albumStore.isUnknown(album.value))

const shuffle = async () => {
  await playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value), true /* shuffled */)
}

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
