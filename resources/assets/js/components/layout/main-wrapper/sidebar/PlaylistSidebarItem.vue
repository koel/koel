<template>
  <SidebarItem
    :class="{ droppable }"
    :href="href"
    class="playlist select-none"
    draggable="true"
    :active
    @contextmenu="onContextMenu"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart="onDragStart"
    @drop="onDrop"
  >
    <template #icon>
      <Icon v-if="isRecentlyPlayedList(list)" :icon="faClockRotateLeft" class="text-k-success" fixed-width />
      <Icon v-else-if="isFavoriteList(list)" :icon="faStar" class="text-k-highlight" fixed-width />
      <Icon v-else-if="list.is_smart" :icon="faWandMagicSparkles" fixed-width />
      <Icon v-else-if="list.is_collaborative" :icon="faUsers" fixed-width />
      <ListMusicIcon v-else :size="16" />
    </template>
    {{ list.name }}
  </SidebarItem>
</template>

<script lang="ts" setup>
import { faClockRotateLeft, faStar, faUsers, faWandMagicSparkles } from '@fortawesome/free-solid-svg-icons'
import { ListMusicIcon } from 'lucide-vue-next'
import { computed, ref, toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { useDraggable, useDroppable } from '@/composables/useDragAndDrop'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { useContextMenu } from '@/composables/useContextMenu'

import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'

const props = defineProps<{ list: PlaylistLike }>()

const PlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))

const { url, isCurrentScreen, getRouteParam } = useRouter()
const { startDragging } = useDraggable('playlist')
const { acceptsDrop, resolveDroppedItems } = useDroppable(['playables', 'album', 'artist', 'browser-media'])
const { openContextMenu } = useContextMenu()

const droppable = ref(false)

const { addToPlaylist } = usePlaylistContentManagement()

const { list } = toRefs(props)

const isPlaylist = (list: PlaylistLike): list is Playlist => 'id' in list
const isFavoriteList = (list: PlaylistLike): list is FavoriteList => list.name === 'Favorites'
const isRecentlyPlayedList = (list: PlaylistLike): list is RecentlyPlayedList => list.name === 'Recently Played'

const active = computed(() => {
  return (isCurrentScreen('Favorites') && isFavoriteList(list.value))
    || (isCurrentScreen('RecentlyPlayed') && isRecentlyPlayedList(list.value))
    || (isCurrentScreen('Playlist') && (list.value as Playlist).id === getRouteParam('id'))
})

const href = computed(() => {
  if (isPlaylist(list.value)) {
    return url('playlists.show', { id: list.value.id })
  }

  if (isFavoriteList(list.value)) {
    return url('favorites')
  }

  if (isRecentlyPlayedList(list.value)) {
    return url('recently-played')
  }

  throw new Error('Invalid playlist-like type.')
})

const contentEditable = computed(() => {
  if (isRecentlyPlayedList(list.value)) {
    return false
  }
  if (isFavoriteList(list.value)) {
    return true
  }

  return !list.value.is_smart
})

const onContextMenu = (event: MouseEvent) => {
  if (isPlaylist(list.value)) {
    event.preventDefault()
    openContextMenu<'PLAYLIST'>(PlaylistContextMenu, event, {
      playlist: list.value,
    })
  }
}

const onDragStart = (event: DragEvent) => isPlaylist(list.value) && startDragging(event, list.value)

const onDragOver = (event: DragEvent) => {
  if (!contentEditable.value) {
    return false
  }
  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()
  droppable.value = true

  return false
}

const onDragLeave = () => (droppable.value = false)

const onDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!contentEditable.value) {
    return false
  }
  if (!acceptsDrop(event)) {
    return false
  }

  const playables = await resolveDroppedItems(event)

  if (!playables?.length) {
    return false
  }

  if (isFavoriteList(list.value)) {
    await playableStore.favorite(playables)
  } else if (isPlaylist(list.value)) {
    await addToPlaylist(list.value, playables)
  }

  return false
}
</script>

<style lang="postcss" scoped>
.droppable {
  @apply ring-1 ring-offset-0 ring-k-accent rounded-md cursor-copy;
}
</style>
