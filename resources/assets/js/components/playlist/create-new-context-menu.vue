<template>
  <BaseContextMenu extra-class="playlist-menu" ref="base">
    <li @click="createPlaylist" data-testid="playlist-context-menu-create-simple">New Playlist</li>
    <li @click="createSmartPlaylist" data-testid="playlist-context-menu-create-smart">New Smart Playlist</li>
  </BaseContextMenu>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { base, BaseContextMenu, open, close } = useContextMenu()

const emit = defineEmits(['createPlaylist'])

const createPlaylist = () => {
  emit('createPlaylist')
  close()
}

const createSmartPlaylist = () => {
  eventBus.emit('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM')
  close()
}
</script>
