<template>
  <li
    :class="{ droppable, 'drop-target-path': isOnDropTargetPath }"
    class="playlist-folder relative"
    :draggable="!isMobile.any"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart.stop="onDragStart"
    @drop="onDrop"
  >
    <ul>
      <SidebarItem tabindex="0" @click="toggle" @contextmenu.prevent.stop="onContextMenu">
        <template #icon>
          <Icon :icon="opened ? faFolderOpen : faFolder" fixed-width />
        </template>
        {{ folder.name }}
      </SidebarItem>

      <li v-if="opened && (childFolders.length || playlistsInFolder.length)">
        <ul class="pl-4">
          <PlaylistFolderSidebarItem v-for="child in childFolders" :key="child.id" :folder="child" />
          <PlaylistSidebarItem v-for="playlist in playlistsInFolder" :key="playlist.id" :list="playlist" />
        </ul>
      </li>
    </ul>
  </li>
</template>

<script lang="ts" setup>
import { faFolder, faFolderOpen } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { computed, inject, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { setDragText, useDraggable, useDroppable } from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'
import { DraggedPlaylistFolderKey, DraggedPlaylistKey, PlaylistFolderDropTargetKey } from '@/config/symbols'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import SidebarItem from './SidebarItem.vue'

const props = defineProps<{ folder: PlaylistFolder }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistFolderContextMenu.vue'))

const { folder } = toRefs(props)

const { acceptsDrop, resolveDroppedValue } = useDroppable(['playlist', 'playlist-folder'])
const { startDragging } = useDraggable('playlist-folder')
const { openContextMenu } = useContextMenu()

const folderDropTargetId = inject(PlaylistFolderDropTargetKey, ref<string | null>(null))
const draggedPlaylist = inject(DraggedPlaylistKey, ref<Playlist | null>(null))
const draggedPlaylistFolder = inject(DraggedPlaylistFolderKey, ref<PlaylistFolder | null>(null))

const opened = ref(false)
const droppable = ref(false)
const expandTimeout = ref<number | null>(null)

const playlistsInFolder = computed(() => playlistStore.byFolder(folder.value))
const childFolders = computed(() => playlistFolderStore.byParent(folder.value))
const isOnDropTargetPath = computed(() => {
  if (!folderDropTargetId.value) {
    return false
  }

  return (
    folderDropTargetId.value === folder.value.id ||
    playlistFolderStore.descendantsOf(folder.value).some(descendant => descendant.id === folderDropTargetId.value)
  )
})

const toggle = () => (opened.value = !opened.value)

const acceptsFolderMove = (draggedFolder: PlaylistFolder) =>
  draggedFolder.id !== folder.value.id &&
  draggedFolder.parent_id !== folder.value.id &&
  !playlistFolderStore.descendantsOf(draggedFolder).some(descendant => descendant.id === folder.value.id)

const cancelAutoExpand = () => {
  if (expandTimeout.value !== null) {
    window.clearTimeout(expandTimeout.value)
    expandTimeout.value = null
  }
}

// dragend always fires on the source, regardless of drop-handler stopPropagation.
const clearOnDragEnd = () => {
  droppable.value = false
  cancelAutoExpand()

  if (folderDropTargetId.value === folder.value.id) {
    folderDropTargetId.value = null
  }
}

onMounted(() => document.addEventListener('dragend', clearOnDragEnd))

onBeforeUnmount(() => {
  cancelAutoExpand()
  document.removeEventListener('dragend', clearOnDragEnd)
})

const onDragStart = (event: DragEvent) => {
  startDragging(event, folder.value)
  draggedPlaylistFolder.value = folder.value
}

const onDragOver = (event: DragEvent) => {
  const draggedFolder = draggedPlaylistFolder.value
  if (draggedFolder && !acceptsFolderMove(draggedFolder)) {
    event.stopPropagation()
    droppable.value = false
    cancelAutoExpand()
    folderDropTargetId.value = null
    setDragText('')
    return false
  }

  // Auto-expand so the user can drop on a playlist inside.
  if (!opened.value && expandTimeout.value === null) {
    expandTimeout.value = window.setTimeout(() => {
      opened.value = true
      expandTimeout.value = null
    }, 500)
  }

  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()
  event.stopPropagation()

  // macOS ignores CSS cursor: during DnD; dropEffect drives the native + cursor.
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = draggedFolder ? 'move' : 'copy'
  }

  droppable.value = true
  folderDropTargetId.value = folder.value.id

  if (draggedFolder) {
    setDragText(`Move ${draggedFolder.name} to ${folder.value.name}`)
  } else if (draggedPlaylist.value) {
    const playlist = draggedPlaylist.value
    setDragText(playlist.folder_id === folder.value.id ? '' : `Move ${playlist.name} to ${folder.value.name}`)
  }
}

const onDragLeave = (event: DragEvent) => {
  // dragleave also fires when entering a child — ignore unless cursor is truly outside.
  const relatedTarget = event.relatedTarget as Node | null
  if (relatedTarget && (event.currentTarget as Node).contains(relatedTarget)) {
    return
  }

  droppable.value = false
  cancelAutoExpand()

  if (folderDropTargetId.value === folder.value.id) {
    folderDropTargetId.value = null
  }
}

const onDrop = async (event: DragEvent) => {
  cancelAutoExpand()
  droppable.value = false

  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()
  event.stopPropagation()

  const dropped = await resolveDroppedValue<Playlist | PlaylistFolder>(event)
  if (!dropped) {
    return
  }

  if (dropped.type === 'playlist-folders') {
    await playlistFolderStore.moveFolderToFolder(dropped, folder.value)
    return
  }

  await playlistFolderStore.movePlaylistToFolder(dropped, folder.value)
}

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'PLAYLIST_FOLDER'>(ContextMenu, event, {
    folder: folder.value,
  })
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.droppable {
  @apply cursor-copy;
}
</style>
