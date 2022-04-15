<template>
  <BaseContextMenu extra-class="playlist-item-menu" ref="base">
    <li @click="editPlaylist" :data-testid="`playlist-context-menu-edit-${playlist.id}`">Edit</li>
    <li @click="deletePlaylist" :data-testid="`playlist-context-menu-delete-${playlist.id}`">Delete</li>
  </BaseContextMenu>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { base, BaseContextMenu, open, close } = useContextMenu()

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const emit = defineEmits(['edit'])

const editPlaylist = () => {
  playlist.value.is_smart ? eventBus.emit('MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', playlist.value) : emit('edit')
  close()
}

const deletePlaylist = () => {
  eventBus.emit('PLAYLIST_DELETE', playlist.value)
  close()
}
</script>
