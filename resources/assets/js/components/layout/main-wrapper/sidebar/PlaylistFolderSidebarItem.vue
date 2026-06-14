<template>
  <li
    :class="{ droppable, 'drag-source': isDragSource, 'drag-target': isDropTarget }"
    class="playlist-folder relative"
    :draggable="!isMobile.any"
    tabindex="0"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart="onDragStart"
    @drop="onDrop"
  >
    <ul>
      <SidebarItem @click="toggle" @contextmenu.prevent="onContextMenu">
        <template #icon>
          <Icon :icon="opened ? faFolderOpen : faFolder" fixed-width />
        </template>
        {{ folder.name }}
      </SidebarItem>

      <li v-if="playlistsInFolder.length" v-show="opened">
        <ul>
          <PlaylistSidebarItem
            v-for="playlist in playlistsInFolder"
            :key="playlist.id"
            :list="playlist"
            class="pl-10"
          />
        </ul>
      </li>
    </ul>
  </li>
</template>

<script lang="ts" setup>
import { faFolder, faFolderOpen } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import {
  currentDraggedPlaylist,
  currentDropTargetFolderId,
  useDraggable,
  useDroppable,
} from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import SidebarItem from './SidebarItem.vue'

const props = defineProps<{ folder: PlaylistFolder }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistFolderContextMenu.vue'))

const { folder } = toRefs(props)

const { acceptsDrop, resolveDroppedValue } = useDroppable(['playlist'])
const { startDragging } = useDraggable('playlist-folder')
const { openContextMenu } = useContextMenu()

const opened = ref(false)
const droppable = ref(false)
const expandTimeout = ref<number | null>(null)

const playlistsInFolder = computed(() => playlistStore.byFolder(folder.value))

// Whether the playlist currently being dragged came from this folder.
const isDragSource = computed(() => currentDraggedPlaylist.value?.folder_id === folder.value.id)

// Whether the cursor is currently over this folder as an accepting drop target.
const isDropTarget = computed(() => currentDropTargetFolderId.value === folder.value.id)

const toggle = () => (opened.value = !opened.value)

const cancelAutoExpand = () => {
  if (expandTimeout.value !== null) {
    window.clearTimeout(expandTimeout.value)
    expandTimeout.value = null
  }
}

// `dragend` fires on the drag source whenever the operation ends, regardless of
// where the drop landed or whether a child handler swallowed the event with
// stopPropagation. Use it as the reliable signal to clear the indicator.
const clearOnDragEnd = () => {
  droppable.value = false
  cancelAutoExpand()
}

onMounted(() => document.addEventListener('dragend', clearOnDragEnd))

onBeforeUnmount(() => {
  cancelAutoExpand()
  document.removeEventListener('dragend', clearOnDragEnd)
})

const onDragStart = (event: DragEvent) => startDragging(event, folder.value)

const onDragOver = (event: DragEvent) => {
  // Auto-expand the folder after a brief hover so the user can see what's inside,
  // or drop on a playlist within.
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
  droppable.value = true
  currentDropTargetFolderId.value = folder.value.id
}

const onDragLeave = (event: DragEvent) => {
  // `dragleave` fires when crossing into child elements, too. Only treat it as a
  // real leave when the cursor moves outside the folder's bounding element.
  const relatedTarget = event.relatedTarget as Node | null
  if (relatedTarget && (event.currentTarget as Node).contains(relatedTarget)) {
    return
  }

  droppable.value = false
  cancelAutoExpand()

  if (currentDropTargetFolderId.value === folder.value.id) {
    currentDropTargetFolderId.value = null
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

  const playlist = await resolveDroppedValue<Playlist>(event)
  if (!playlist) {
    return
  }

  await playlistFolderStore.movePlaylistToFolder(playlist, folder.value)
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
