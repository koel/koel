<template>
  <ContextMenu ref="base">
    <li data-testid="playlist-context-menu-create-simple" @click="onItemClicked('new-playlist')">New Playlist…</li>
    <li data-testid="playlist-context-menu-create-smart" @click="onItemClicked('new-smart-playlist')">
      New Smart Playlist…
    </li>
    <li data-testid="playlist-context-menu-create-folder" @click="onItemClicked('new-folder')">New Folder…</li>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { useContextMenu } from '@/composables'
import { eventBus } from '@/utils'
import { Events } from '@/config'

const { base, ContextMenu, open, trigger } = useContextMenu()

type Action = 'new-playlist' | 'new-smart-playlist' | 'new-folder'

const actionToEventMap: Record<Action, keyof Events> = {
  'new-playlist': 'MODAL_SHOW_CREATE_PLAYLIST_FORM',
  'new-smart-playlist': 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM',
  'new-folder': 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM'
}

const onItemClicked = (key: keyof typeof actionToEventMap) => trigger(() => eventBus.emit(actionToEventMap[key]))

eventBus.on('CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED', async ({ top, left }) => await open(top, left))
</script>
