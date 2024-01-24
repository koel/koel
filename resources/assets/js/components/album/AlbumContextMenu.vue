<template>
  <ContextMenuBase ref="base" data-testid="album-context-menu" extra-class="album-menu">
    <template v-if="album">
      <li @click="play">Play All</li>
      <li @click="shuffle">Shuffle All</li>
      <li class="separator" />
      <li v-if="isStandardAlbum" @click="viewAlbumDetails">Go to Album</li>
      <li v-if="isStandardArtist" @click="viewArtistDetails">Go to Artist</li>
      <template v-if="isStandardAlbum && allowDownload">
        <li class="separator" />
        <li @click="download">Download</li>
      </template>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useContextMenu, useRouter } from '@/composables'
import { eventBus } from '@/utils'

const { go } = useRouter()
const { base, ContextMenuBase, open, trigger } = useContextMenu()

const album = ref<Album>()
const allowDownload = toRef(commonStore.state, 'allows_download')

const isStandardAlbum = computed(() => !albumStore.isUnknown(album.value!))

const isStandardArtist = computed(() => {
  return !artistStore.isUnknown(album.value!.artist_id) && !artistStore.isVarious(album.value!.artist_id)
})

const play = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value!))
  go('queue')
})

const shuffle = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value!), true)
  go('queue')
})

const viewAlbumDetails = () => trigger(() => go(`album/${album.value!.id}`))
const viewArtistDetails = () => trigger(() => go(`artist/${album.value!.artist_id}`))
const download = () => trigger(() => downloadService.fromAlbum(album.value!))

eventBus.on('ALBUM_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _album) => {
  album.value = _album
  await open(pageY, pageX)
})
</script>
