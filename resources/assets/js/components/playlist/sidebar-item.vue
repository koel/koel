<template>
  <li
    @dblclick.prevent="makeEditable"
    :class="['playlist', type, editing ? 'editing' : '', playlist.is_smart ? 'smart' : '']">
    <a
      :class="{ active }"
      :href="url"
      @contextmenu.prevent="openContextMenu"
      v-koel-droppable="handleDrop"
    >{{ playlist.name }}</a>

    <NameEditor
      :playlist="playlist"
      @cancelled="cancelEditing"
      @updated="onPlaylistNameUpdated"
      v-if="nameEditable && editing"
    />

    <ContextMenu
      v-if="hasContextMenu"
      v-show="showingContextMenu"
      :playlist="playlist"
      ref="contextMenu"
      @edit="makeEditable"
    />
  </li>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, ref, toRefs } from 'vue'
import { BaseContextMenu } from 'koel/types/ui'
import { eventBus } from '@/utils'
import router from '@/router'
import { favoriteStore, playlistStore, songStore } from '@/stores'

type PlaylistType = 'playlist' | 'favorites' | 'recently-played'

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/item-context-menu.vue'))
const NameEditor = defineAsyncComponent(() => import('@/components/playlist/name-editor.vue'))

const contextMenu = ref<BaseContextMenu | null>(null)

const props = withDefaults(defineProps<{ playlist: Playlist, type: PlaylistType }>(), { type: 'playlist' })
const { playlist, type } = toRefs(props)

const editing = ref(false)
const active = ref(false)
const showingContextMenu = ref(false)

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

const nameEditable = computed(() => type.value === 'playlist')
const hasContextMenu = computed(() => type.value === 'playlist')

const contentEditable = computed(() => {
  if (playlist.value.is_smart) {
    return false
  }

  return type.value === 'playlist' || type.value === 'favorites'
})

const makeEditable = () => {
  if (!nameEditable.value) {
    return
  }

  editing.value = true
}

/**
 * Handle songs dropped to our favorite or playlist menu item.
 */
const handleDrop = (event: DragEvent) => {
  if (!contentEditable.value) {
    return false
  }

  if (!event.dataTransfer?.getData('application/x-koel.text+plain')) {
    return false
  }

  const songs = songStore.byIds(event.dataTransfer.getData('application/x-koel.text+plain').split(','))

  if (!songs.length) {
    return false
  }

  if (type.value === 'favorites') {
    favoriteStore.like(songs)
  } else if (type.value === 'playlist') {
    playlistStore.addSongs(playlist.value, songs)
  }

  return false
}

const openContextMenu = async (event: MouseEvent) => {
  if (hasContextMenu.value) {
    showingContextMenu.value = true
    await nextTick()
    router.go(`/playlist/${playlist.value.id}`)
    contextMenu.value?.open(event.pageY, event.pageX)
  }
}

const cancelEditing = () => (editing.value = false)

const onPlaylistNameUpdated = (mutatedPlaylist: Playlist) => {
  playlist.value.name = mutatedPlaylist.name
  editing.value = false
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

  a {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

    span {
      pointer-events: none;
    }

    &::before {
      content: "\f0f6";
    }
  }

  &.favorites a::before {
    content: "\f004";
    color: var(--color-maroon);
  }

  &.recently-played a::before {
    content: "\f1da";
    color: var(--color-green);
  }

  &.smart a::before {
    content: "\f069";
  }

  input {
    width: calc(100% - 32px);
    margin: 5px 16px;
  }

  &.editing {
    a {
      display: none !important;
    }
  }
}
</style>
