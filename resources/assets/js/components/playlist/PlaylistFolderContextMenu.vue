<template>
  <ContextMenuBase ref="base" data-testid="playlist-folder-context-menu" extra-class="playlist-folder-menu">
    <template v-if="folder">
      <template v-if="playable">
        <li data-testid="play" @click="play">Play All</li>
        <li data-testid="play" @click="shuffle">Shuffle All</li>
        <li class="separator"/>
      </template>
      <li data-testid="shuffle" @click="rename">Rename</li>
      <li data-testid="shuffle" @click="destroy">Delete</li>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useContextMenu } from '@/composables'
import { eventBus, requireInjection } from '@/utils'
import { playlistFolderStore, playlistStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { DialogBoxKey } from '@/symbols'
import router from '@/router'

const { context, base, ContextMenuBase, open, trigger } = useContextMenu()

const dialog = requireInjection(DialogBoxKey)
const folder = ref<PlaylistFolder>()

const playlistsInFolder = computed(() => folder.value ? playlistStore.byFolder(folder.value) : [])
const playable = computed(() => playlistsInFolder.value.length > 0)

const play = () => trigger(async () => {
  await playbackService.queueAndPlay(await songStore.fetchForPlaylistFolder(folder.value!))
  router.go('queue')
})

const shuffle = () => trigger(async () => {
  await playbackService.queueAndPlay(await songStore.fetchForPlaylistFolder(folder.value!), true)
  router.go('queue')
})

const rename = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder.value!))

const destroy = () => trigger(async () => {
  await dialog.value.confirm('Delete this folder?') && await playlistFolderStore.delete(folder.value!)
})

eventBus.on('PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, _folder: PlaylistFolder) => {
  folder.value = _folder
  await open(e.pageY, e.pageX, { folder })
})
</script>
