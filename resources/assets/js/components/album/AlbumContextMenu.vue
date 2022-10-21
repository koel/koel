<template>
  <ContextMenuBase ref="base" data-testid="album-context-menu" extra-class="album-menu">
    <template v-if="album">
      <li data-testid="play" @click="play">Play All</li>
      <li data-testid="shuffle" @click="shuffle">Shuffle All</li>
      <li class="separator"></li>
      <li v-if="isStandardAlbum" data-testid="view-album" @click="viewAlbumDetails">Go to Album</li>
      <li v-if="isStandardArtist" data-testid="view-artist" @click="viewArtistDetails">Go to Artist</li>
      <template v-if="isStandardAlbum && allowDownload">
        <li class="separator"></li>
        <li data-testid="download" @click="download">Download</li>
      </template>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useContextMenu } from '@/composables'
import { eventBus, requireInjection } from '@/utils'
import { RouterKey } from '@/symbols'

const { context, base, ContextMenuBase, open, trigger } = useContextMenu()
const router = requireInjection(RouterKey)

const album = ref<Album>()
const allowDownload = toRef(commonStore.state, 'allow_download')

const isStandardAlbum = computed(() => !albumStore.isUnknown(album.value!))

const isStandardArtist = computed(() => {
  return !artistStore.isUnknown(album.value!.artist_id) && !artistStore.isVarious(album.value!.artist_id)
})

const play = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value!))
  router.go('queue')
})

const shuffle = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value!), true)
  router.go('queue')
})

const viewAlbumDetails = () => trigger(() => router.go(`album/${album.value!.id}`))
const viewArtistDetails = () => trigger(() => router.go(`artist/${album.value!.artist_id}`))
const download = () => trigger(() => downloadService.fromAlbum(album.value!))

eventBus.on('ALBUM_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, _album: Album) => {
  album.value = _album
  await open(e.pageY, e.pageX, { album })
})
</script>
