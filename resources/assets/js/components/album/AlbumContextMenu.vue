<template>
  <ContextMenuBase extra-class="album-menu" data-testid="album-context-menu" ref="base">
    <template v-if="album">
      <li data-test="play" @click="play">Play All</li>
      <li data-test="shuffle" @click="shuffle">Shuffle All</li>
      <li class="separator"></li>
      <li data-test="view-album" @click="viewAlbumDetails" v-if="isStandardAlbum">Go to Album</li>
      <li data-test="view-artist" @click="viewArtistDetails" v-if="isStandardArtist">Go to Artist</li>
      <template v-if="isStandardAlbum && sharedState.allowDownload">
        <li class="separator"></li>
        <li data-test="download" @click="download">Download</li>
      </template>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, reactive, Ref, toRef } from 'vue'
import { albumStore, artistStore, sharedStore } from '@/stores'
import { download as downloadService, playback } from '@/services'
import { useContextMenu } from '@/composables'
import router from '@/router'

const { context, base, ContextMenuBase, open, close } = useContextMenu()

const album = toRef(context, 'album') as Ref<Album>

const sharedState = reactive(sharedStore.state)

const isStandardAlbum = computed(() => !albumStore.isUnknownAlbum(album.value))

const isStandardArtist = computed(() => {
  return !artistStore.isUnknownArtist(album.value.artist) && !artistStore.isVariousArtists(album.value.artist)
})

const play = () => playback.playAllInAlbum(album.value)
const shuffle = () => playback.playAllInAlbum(album.value, true /* shuffled */)

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
