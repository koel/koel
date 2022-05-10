<template>
  <ContextMenuBase extra-class="album-menu" data-testid="album-context-menu" ref="base">
    <template v-if="album">
      <li data-testid="play" @click="play">Play All</li>
      <li data-testid="shuffle" @click="shuffle">Shuffle All</li>
      <li class="separator"></li>
      <li data-testid="view-album" @click="viewAlbumDetails" v-if="isStandardAlbum">Go to Album</li>
      <li data-testid="view-artist" @click="viewArtistDetails" v-if="isStandardArtist">Go to Artist</li>
      <template v-if="isStandardAlbum && allowDownload">
        <li class="separator"></li>
        <li data-testid="download" @click="download">Download</li>
      </template>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, Ref, toRef } from 'vue'
import { albumStore, artistStore, commonStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useContextMenu } from '@/composables'
import router from '@/router'

const { context, base, ContextMenuBase, open, close } = useContextMenu()

const album = toRef(context, 'album') as Ref<Album>
const allowDownload = toRef(commonStore.state, 'allowDownload')

const isStandardAlbum = computed(() => !albumStore.isUnknownAlbum(album.value))

const isStandardArtist = computed(() => {
  return !artistStore.isUnknownArtist(album.value.artist) && !artistStore.isVariousArtists(album.value.artist)
})

const play = () => playbackService.playAllInAlbum(album.value)
const shuffle = () => playbackService.playAllInAlbum(album.value, true /* shuffled */)

const viewAlbumDetails = () => {
  router.go(`album/${album.value.id}`)
  close()
}

const viewArtistDetails = () => {
  router.go(`artist/${album.value.artist.id}`)
  close()
}

const download = () => {
  downloadService.fromAlbum(album.value)
  close()
}

defineExpose({ open, close })
</script>
