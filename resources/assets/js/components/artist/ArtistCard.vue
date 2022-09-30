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
    @dragstart="onDragStart"
    @contextmenu.prevent="requestContextMenu"
  >
    <ArtistThumbnail :entity="artist"/>

    <footer>
      <div class="info">
        <a :href="`#!/artist/${artist.id}`" class="name" data-testid="name">{{ artist.name }}</a>
      </div>
      <p class="meta">
        <span class="left">
          {{ pluralize(artist.album_count, 'album') }}
          •
          {{ pluralize(artist.song_count, 'song') }}
          •
          {{ pluralize(artist.play_count, 'play') }}
        </span>
        <span class="right">
          <a
            :title="`Shuffle all songs by ${artist.name}`"
            class="shuffle-artist"
            data-testid="shuffle-artist"
            href
            role="button"
            @click.prevent="shuffle"
          >
            <icon :icon="faRandom"/>
          </a>
          <a
            v-if="allowDownload"
            :title="`Download all songs by ${artist.name}`"
            class="download-artist"
            data-testid="download-artist"
            href
            role="button"
            @click.prevent="download"
          >
            <icon :icon="faDownload"/>
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import { faDownload, faRandom } from '@fortawesome/free-solid-svg-icons'
import { computed, toRef, toRefs } from 'vue'
import { eventBus, pluralize, requireInjection } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useDraggable } from '@/composables'
import { RouterKey } from '@/symbols'

import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

const router = requireInjection(RouterKey)

const { startDragging } = useDraggable('artist')

const props = withDefaults(defineProps<{ artist: Artist, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { artist, layout } = toRefs(props)

const allowDownload = toRef(commonStore.state, 'allow_download')

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  await playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value), true /* shuffled */)
  router.go('queue')
}

const download = () => downloadService.fromArtist(artist.value)
const onDragStart = (event: DragEvent) => startDragging(event, artist.value)
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>

<style lang="scss" scoped>
@include artist-album-card();
</style>
