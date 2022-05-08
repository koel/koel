<template>
  <li
    :class="['playlist', type, editing ? 'editing' : '', playlist.is_smart ? 'smart' : '']"
    data-testid="playlist-sidebar-item"
    @dblclick.prevent="makeEditable"
  >
    <a
      v-koel-droppable:[contentEditable]="handleDrop"
      :class="{ active }"
      :href="url"
      @contextmenu.prevent="openContextMenu"
    >
      {{ playlist.name }}
    </a>

    <NameEditor
      v-if="nameEditable && editing"
      :playlist="playlist"
      @cancelled="cancelEditing"
      @updated="onPlaylistNameUpdated"
    />

    <ContextMenu v-if="hasContextMenu" ref="contextMenu" :playlist="playlist" @edit="makeEditable"/>
  </li>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, ref, toRefs } from 'vue'
import { alerts, eventBus, pluralize } from '@/utils'
import { favoriteStore, playlistStore, songStore } from '@/stores'
import router from '@/router'

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))
const NameEditor = defineAsyncComponent(() => import('@/components/playlist/PlaylistNameEditor.vue'))

const contextMenu = ref<InstanceType<typeof ContextMenu>>()

const props = withDefaults(defineProps<{ playlist: Playlist, type?: PlaylistType }>(), { type: 'playlist' })
const { playlist, type } = toRefs(props)

const editing = ref(false)
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
    alerts.success(`Added ${pluralize(songs.length, 'song')} into "${playlist.value.name}."`)
  }

  return false
}

const openContextMenu = async (event: MouseEvent) => {
  if (hasContextMenu.value) {
    await nextTick()
    router.go(`/playlist/${playlist.value.id}`)
    contextMenu.value?.open(event.pageY, event.pageX, { playlist })
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
