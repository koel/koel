<template>
  <ContextMenu ref="base">
    <template v-if="folder">
      <template v-if="playable">
        <li @click="play">Play All</li>
        <li @click="shuffle">Shuffle All</li>
        <li class="separator" />
      </template>
      <li @click="createPlaylist">New Playlist…</li>
      <li @click="createSmartPlaylist">New Smart Playlist…</li>
      <li class="separator" />
      <li @click="rename">Rename</li>
      <li @click="destroy">Delete</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { playlistStore } from '@/stores/playlistStore'
import { playbackService } from '@/services/playbackService'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { songStore } from '@/stores/songStore'

const { base, ContextMenu, open, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning } = useMessageToaster()

const folder = ref<PlaylistFolder>()

const playlistsInFolder = computed(() => folder.value ? playlistStore.byFolder(folder.value) : [])
const playable = computed(() => playlistsInFolder.value.length > 0)

const play = () => trigger(async () => {
  const songs = await songStore.fetchForPlaylistFolder(folder.value!)

  if (songs.length) {
    playbackService.queueAndPlay(songs)
    go(url('queue'))
  } else {
    toastWarning('No songs available.')
  }
})

const shuffle = () => trigger(async () => {
  const songs = await songStore.fetchForPlaylistFolder(folder.value!)

  if (songs.length) {
    playbackService.queueAndPlay(songs, true)
    go(url('queue'))
  } else {
    toastWarning('No songs available.')
  }
})

const createPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_PLAYLIST_FORM', folder.value!))
const createSmartPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', folder.value!))
const rename = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder.value!))
const destroy = () => trigger(() => eventBus.emit('PLAYLIST_FOLDER_DELETE', folder.value!))

eventBus.on('PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _folder) => {
  folder.value = _folder
  await open(pageY, pageX)
})
</script>
