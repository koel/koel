<template>
  <ContextMenuBase ref="base">
    <template v-if="folder">
      <template v-if="playable">
        <li @click="play">Play All</li>
        <li @click="shuffle">Shuffle All</li>
        <li class="separator"/>
      </template>
      <li @click="rename">Rename</li>
      <li @click="destroy">Delete</li>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useContextMenu } from '@/composables'
import { eventBus, requireInjection } from '@/utils'
import { playlistStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { DialogBoxKey, RouterKey } from '@/symbols'

const { context, base, ContextMenuBase, open, trigger } = useContextMenu()

const dialog = requireInjection(DialogBoxKey)
const router = requireInjection(RouterKey)
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

const rename = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder.value))
const destroy = () => trigger(() => eventBus.emit('PLAYLIST_FOLDER_DELETE', folder.value))

eventBus.on('PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, _folder: PlaylistFolder) => {
  folder.value = _folder
  await open(e.pageY, e.pageX, { folder })
})
</script>
