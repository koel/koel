<template>
  <ContextMenuBase ref="base">
    <li :data-testid="`playlist-context-menu-edit-${playlist.id}`" @click="editPlaylist">
      {{ playlist.is_smart ? 'Edit' : 'Rename' }}
    </li>
    <li :data-testid="`playlist-context-menu-delete-${playlist.id}`" @click="deletePlaylist">Delete</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { context, base, ContextMenuBase, open, trigger } = useContextMenu()
const playlist = ref<Playlist>()

const editPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value))
const deletePlaylist = () => trigger(() => eventBus.emit('PLAYLIST_DELETE', playlist.value))

onMounted(() => {
  eventBus.on('PLAYLIST_CONTEXT_MENU_REQUESTED', async (event: MouseEvent, _playlist: Playlist) => {
    playlist.value = _playlist
    await open(event.pageY, event.pageX, { playlist })
  })
})
</script>
