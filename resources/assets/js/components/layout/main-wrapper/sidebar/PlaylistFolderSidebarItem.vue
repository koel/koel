<template>
  <li
    :class="{ droppable }"
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
import { useDraggable, useDroppable } from '@/composables/useDragAndDrop'
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

  // The browser's drag-and-drop cursor on macOS ignores CSS `cursor:`; setting
  // dropEffect explicitly is how we get the copy (+) cursor while hovering.
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'copy'
  }

  droppable.value = true
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
