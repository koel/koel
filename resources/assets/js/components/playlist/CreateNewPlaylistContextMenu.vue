<template>
  <ContextMenuBase ref="base" extra-class="playlist-menu">
    <li data-testid="playlist-context-menu-create-simple" @click="createPlaylist">New Playlist</li>
    <li data-testid="playlist-context-menu-create-smart" @click="createSmartPlaylist">New Smart Playlist</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { base, ContextMenuBase, open, close } = useContextMenu()

const emit = defineEmits(['createPlaylist'])

const createPlaylist = () => {
  emit('createPlaylist')
  close()
}

const createSmartPlaylist = () => {
  eventBus.emit('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM')
  close()
}

defineExpose({ open })
</script>
