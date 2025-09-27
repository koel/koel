<template>
  <ul>
    <MenuItem @click="onItemClicked('new-playlist')">New Playlist…</MenuItem>
    <MenuItem @click="onItemClicked('new-smart-playlist')">New Smart Playlist…</MenuItem>
    <MenuItem @click="onItemClicked('new-folder')">New Folder…</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { useContextMenu } from '@/composables/useContextMenu'
import { eventBus } from '@/utils/eventBus'
import type { Events } from '@/config/events'

const { MenuItem, trigger } = useContextMenu()

type Action = 'new-playlist' | 'new-smart-playlist' | 'new-folder'

const actionToEventMap: Record<Action, keyof Events> = {
  'new-playlist': 'MODAL_SHOW_CREATE_PLAYLIST_FORM',
  'new-smart-playlist': 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM',
  'new-folder': 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM',
}

const onItemClicked = (key: keyof typeof actionToEventMap) => trigger(() => eventBus.emit(actionToEventMap[key]))
</script>
