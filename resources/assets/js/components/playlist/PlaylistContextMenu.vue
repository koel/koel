<template>
  <ContextMenuBase ref="base">
    <li @click="editPlaylist">Edit…</li>
    <li @click="deletePlaylist">Delete</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { base, ContextMenuBase, open, trigger } = useContextMenu()
const playlist = ref<Playlist>()

const editPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value!))
const deletePlaylist = () => trigger(() => eventBus.emit('PLAYLIST_DELETE', playlist.value!))

eventBus.on('PLAYLIST_CONTEXT_MENU_REQUESTED', async (event, _playlist) => {
  playlist.value = _playlist
  await open(event.pageY, event.pageX)
})
</script>
