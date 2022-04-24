<template>
  <ContextMenuBase extra-class="playlist-item-menu" ref="base">
    <li @click="editPlaylist" :data-testid="`playlist-context-menu-edit-${playlist.id}`">Edit</li>
    <li @click="deletePlaylist" :data-testid="`playlist-context-menu-delete-${playlist.id}`">Delete</li>
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
