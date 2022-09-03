<template>
  <li
    ref="el"
    :class="['playlist', type, playlist.is_smart ? 'smart' : '']"
    data-testid="playlist-sidebar-item"
    draggable="true"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart="onDragStart"
    @drop="onDrop"
  >
    <a :class="{ active }" :href="url" @contextmenu.prevent="onContextMenu">
      <icon v-if="type === 'recently-played'" :icon="faClockRotateLeft" class="text-green" fixed-width/>
      <icon v-else-if="type === 'favorites'" :icon="faHeart" class="text-maroon" fixed-width/>
      <icon
        v-else-if="playlist.is_smart"
        :icon="faBoltLightning"
        :mask="faFile"
        fixed-width
        transform="shrink-7 down-2"
      />
      <icon v-else :icon="faMusic" :mask="faFile" fixed-width transform="shrink-7 down-2"/>
      {{ playlist.name }}
    </a>

    <ContextMenu v-if="hasContextMenu" ref="contextMenu" :playlist="playlist"/>
  </li>
</template>

<script lang="ts" setup>
import { faBoltLightning, faClockRotateLeft, faFile, faHeart, faMusic } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, ref, toRefs } from 'vue'
import { eventBus, pluralize, requireInjection } from '@/utils'
import { favoriteStore, playlistStore } from '@/stores'
import router from '@/router'
import { MessageToasterKey } from '@/symbols'
import { useDraggable, useDroppable } from '@/composables'

import ContextMenu from '@/components/playlist/PlaylistContextMenu.vue'

const { startDragging } = useDraggable('playlist')
const { acceptsDrop, resolveDroppedSongs } = useDroppable(['songs', 'album', 'artist'])

const toaster = requireInjection(MessageToasterKey)
const contextMenu = ref<InstanceType<typeof ContextMenu>>()
const el = ref<HTMLLIElement>()

const props = withDefaults(defineProps<{ playlist: Playlist, type?: PlaylistType }>(), { type: 'playlist' })
const { playlist, type } = toRefs(props)

const active = ref(false)

const url = computed(() => {
  switch (type.value) {
    case 'playlist':
      return `#!/playlist/${playlist.value.id}`

    case 'favorites':
      return '#!/favorites'

    case 'recently-played':
      return '#!/recently-played'

    default:
      throw new Error('Invalid playlist type')
  }
})

const hasContextMenu = computed(() => type.value === 'playlist')

const contentEditable = computed(() => {
  if (playlist.value.is_smart) return false
  return type.value === 'playlist' || type.value === 'favorites'
})

const onContextMenu = async (event: MouseEvent) => {
  if (hasContextMenu.value) {
    await nextTick()
    router.go(`/playlist/${playlist.value.id}`)
    contextMenu.value?.open(event.pageY, event.pageX, { playlist })
  }
}

const onDragStart = (event: DragEvent) => {
  if (type.value === 'playlist') {
    startDragging(event, playlist.value)
  }
}

const onDragOver = (event: DragEvent) => {
  if (!contentEditable.value) return false
  if (!acceptsDrop(event)) return false

  event.preventDefault()
  event.dataTransfer!.dropEffect = 'copy'
  el.value!.classList.add('droppable')

  return false
}

const onDragLeave = () => el.value!.classList.remove('droppable')

const onDrop = async (event: DragEvent) => {
  el.value!.classList.remove('droppable')

  if (!contentEditable.value) return false
  if (!acceptsDrop(event)) return false

  const songs = await resolveDroppedSongs(event)

  if (!songs?.length) return false

  if (type.value === 'favorites') {
    await favoriteStore.like(songs)
  } else if (type.value === 'playlist') {
    await playlistStore.addSongs(playlist.value, songs)
    toaster.value.success(`Added ${pluralize(songs, 'song')} into "${playlist.value.name}."`)
  }

  return false
}

eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName, _playlist: Playlist): void => {
  switch (view) {
    case 'Favorites':
      active.value = type.value === 'favorites'
      break

    case 'RecentlyPlayed':
      active.value = type.value === 'recently-played'

      break
    case 'Playlist':
      active.value = playlist.value === _playlist
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
  overflow: hidden;

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

  input {
    width: calc(100% - 32px);
    margin: 5px 16px;
  }
}
</style>
