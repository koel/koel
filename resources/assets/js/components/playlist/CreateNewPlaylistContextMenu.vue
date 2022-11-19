<template>
  <ContextMenuBase ref="base">
    <li data-testid="playlist-context-menu-create-simple" @click="onItemClicked('new-playlist')">New Playlist</li>
    <li data-testid="playlist-context-menu-create-smart" @click="onItemClicked('new-smart-playlist')">
      New Smart Playlist
    </li>
    <li data-testid="playlist-context-menu-create-folder" @click="onItemClicked('new-folder')">New Folder</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { useContextMenu } from '@/composables'
import { eventBus } from '@/utils'
import { Events } from '@/config'

const { base, ContextMenuBase, open, trigger } = useContextMenu()

const actionToEventMap: Record<string, keyof Events> = {
  'new-playlist': 'MODAL_SHOW_CREATE_PLAYLIST_FORM',
  'new-smart-playlist': 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM',
  'new-folder': 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM'
}

const onItemClicked = (key: keyof typeof actionToEventMap) => trigger(() => eventBus.emit(actionToEventMap[key]))

eventBus.on('CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED', async e => await open(e.pageY, e.pageX))
</script>
