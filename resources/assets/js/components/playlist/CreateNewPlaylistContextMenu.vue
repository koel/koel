<template>
  <ContextMenuBase ref="base" extra-class="playlist-menu">
    <li data-testid="playlist-context-menu-create-simple" @click="createPlaylist">New Playlist</li>
    <li data-testid="playlist-context-menu-create-smart" @click="createSmartPlaylist">New Smart Playlist</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils'
import { useContextMenu } from '@/composables'

const { base, ContextMenuBase, open, trigger } = useContextMenu()

const emit = defineEmits(['createPlaylist'])

const createPlaylist = () => trigger(() => emit('createPlaylist'))
const createSmartPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM'))

defineExpose({ open })
</script>
