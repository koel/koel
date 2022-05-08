<template>
  <ContextMenuBase ref="base" extra-class="playlist-item-menu">
    <li :data-testid="`playlist-context-menu-edit-${playlist.id}`" @click="editPlaylist">Edit</li>
    <li :data-testid="`playlist-context-menu-delete-${playlist.id}`" @click="deletePlaylist">Delete</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { Ref, toRef } from 'vue'
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { context, base, ContextMenuBase, open, close } = useContextMenu()
const playlist = toRef(context, 'playlist') as Ref<Playlist>

const emit = defineEmits(['edit'])

const editPlaylist = () => {
  playlist.value.is_smart ? eventBus.emit('MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', playlist.value) : emit('edit')
  close()
}

const deletePlaylist = () => {
  eventBus.emit('PLAYLIST_DELETE', playlist.value)
  close()
}

defineExpose({ open })
</script>
