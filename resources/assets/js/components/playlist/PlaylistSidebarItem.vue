<template>
  <li
    ref="el"
    :class="{ droppable }"
    class="playlist"
    data-testid="playlist-sidebar-item"
    draggable="true"
    @contextmenu="onContextMenu"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart="onDragStart"
    @drop="onDrop"
  >
    <a :class="{ active }" :href="url">
      <icon v-if="isRecentlyPlayedList(list)" :icon="faClockRotateLeft" class="text-green" fixed-width/>
      <icon v-else-if="isFavoriteList(list)" :icon="faHeart" class="text-maroon" fixed-width/>
      <icon
        v-else-if="list.is_smart"
        :icon="faBoltLightning"
        :mask="faFile"
        fixed-width
        transform="shrink-7 down-2"
      />
      <icon v-else :icon="faMusic" :mask="faFile" fixed-width transform="shrink-7 down-2"/>
      {{ list.name }}
    </a>
  </li>
</template>

<script lang="ts" setup>
import { faBoltLightning, faClockRotateLeft, faFile, faHeart, faMusic } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { eventBus, pluralize, requireInjection } from '@/utils'
import { favoriteStore, playlistStore } from '@/stores'
import { MessageToasterKey, RouterKey } from '@/symbols'
import { useDraggable, useDroppable } from '@/composables'

const { startDragging } = useDraggable('playlist')
const { acceptsDrop, resolveDroppedSongs } = useDroppable(['songs', 'album', 'artist'])

const toaster = requireInjection(MessageToasterKey)
const router = requireInjection(RouterKey)
const droppable = ref(false)

const props = defineProps<{ list: PlaylistLike }>()
const { list } = toRefs(props)

const isPlaylist = (list: PlaylistLike): list is Playlist => 'id' in list
const isFavoriteList = (list: PlaylistLike): list is FavoriteList => list.name === 'Favorites'
const isRecentlyPlayedList = (list: PlaylistLike): list is RecentlyPlayedList => list.name === 'Recently Played'

const active = ref(false)

const url = computed(() => {
  if (isPlaylist(list.value)) return `#/playlist/${list.value.id}`
  if (isFavoriteList(list.value)) return '#/favorites'
  if (isRecentlyPlayedList(list.value)) return '#/recently-played'

  throw new Error('Invalid playlist-like type.')
})

const contentEditable = computed(() => {
  if (isRecentlyPlayedList(list.value)) return false
  if (isFavoriteList(list.value)) return true

  return !list.value.is_smart
})

const onContextMenu = (event: MouseEvent) => {
  if (isPlaylist(list.value)) {
    event.preventDefault()
    eventBus.emit('PLAYLIST_CONTEXT_MENU_REQUESTED', event, list.value)
  }
}

const onDragStart = (event: DragEvent) => isPlaylist(list.value) && startDragging(event, list.value)

const onDragOver = (event: DragEvent) => {
  if (!contentEditable.value) return false
  if (!acceptsDrop(event)) return false

  event.preventDefault()
  event.dataTransfer!.dropEffect = 'copy'
  droppable.value = true

  return false
}

const onDragLeave = () => (droppable.value = false)

const onDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!contentEditable.value) return false
  if (!acceptsDrop(event)) return false

  const songs = await resolveDroppedSongs(event)

  if (!songs?.length) return false

  if (isFavoriteList(list.value)) {
    await favoriteStore.like(songs)
  } else if (isPlaylist(list.value)) {
    await playlistStore.addSongs(list.value, songs)
    toaster.value.success(`Added ${pluralize(songs, 'song')} into "${list.value.name}."`)
  }

  return false
}

router.onRouteChanged(route => {
  switch (route.screen) {
    case 'Favorites':
      active.value = isFavoriteList(list.value)
      break

    case 'RecentlyPlayed':
      active.value = isRecentlyPlayedList(list.value)
      break

    case 'Playlist':
      active.value = (list.value as Playlist).id === parseInt(route.params!.id)
      break

    default:
      active.value = false
      break
  }
})
</script>

<style lang="scss" scoped>
.playlist {
  user-select: none;

  &.droppable {
    box-shadow: inset 0 0 0 1px var(--color-accent);
    border-radius: 4px;
    cursor: copy;
  }

  ::v-deep(a) {
    span {
      pointer-events: none;
    }
  }
}
</style>
