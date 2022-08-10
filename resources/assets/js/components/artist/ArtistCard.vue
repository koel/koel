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
    <ArtistThumbnail :entity="artist"/>

    <footer>
      <div class="info">
        <a class="name" :href="`#!/artist/${artist.id}`" data-testid="name">{{ artist.name }}</a>
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
            href
            role="button"
            data-testid="shuffle-artist"
            @click.prevent="shuffle"
          >
            <icon :icon="faRandom"/>
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
import { eventBus, pluralize, startDragging } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

const props = withDefaults(defineProps<{ artist: Artist, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { artist, layout } = toRefs(props)

const allowDownload = toRef(commonStore.state, 'allow_download')

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  await playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value), true /* shuffled */)
}

const download = () => downloadService.fromArtist(artist.value)
const dragStart = (event: DragEvent) => startDragging(event, artist.value, 'Artist')
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>

<style lang="scss" scoped>
@include artist-album-card();
</style>
