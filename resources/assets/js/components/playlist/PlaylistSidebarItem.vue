<template>
  <li
    :class="['playlist', type, editing ? 'editing' : '', playlist.is_smart ? 'smart' : '']"
    data-testid="playlist-sidebar-item"
    @dblclick.prevent="makeEditable"
  >
    <a
      v-if="contentEditable"
      v-koel-droppable="handleDrop"
      :class="{ active }"
      :href="url"
      @contextmenu.prevent="openContextMenu"
    >
      <icon v-if="type === 'favorites'" :icon="faHeart" class="text-maroon" fixed-width/>
      <icon v-else :icon="faMusic" :mask="faFile" transform="shrink-7 down-2" fixed-width/>
      {{ playlist.name }}
    </a>

    <a v-else :class="{ active }" :href="url" @contextmenu.prevent="openContextMenu">
      <icon v-if="type === 'recently-played'" :icon="faClockRotateLeft" class="text-green" fixed-width/>
      <icon v-else :icon="faBoltLightning" :mask="faFile" transform="shrink-7 down-2" fixed-width/>
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
import { faBoltLightning, faClockRotateLeft, faFile, faHeart, faMusic } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, nextTick, ref, toRefs } from 'vue'
import { eventBus, pluralize, requireInjection, resolveSongsFromDragEvent } from '@/utils'
import { favoriteStore, playlistStore } from '@/stores'
import router from '@/router'
import { MessageToasterKey } from '@/symbols'

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))
const NameEditor = defineAsyncComponent(() => import('@/components/playlist/PlaylistNameEditor.vue'))

const toaster = requireInjection(MessageToasterKey)
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

const handleDrop = async (event: DragEvent) => {
  if (!contentEditable.value) {
    return false
  }

  const songs = await resolveSongsFromDragEvent(event)

  if (!songs.length) {
    return false
  }

  if (type.value === 'favorites') {
    await favoriteStore.like(songs)
  } else if (type.value === 'playlist') {
    await playlistStore.addSongs(playlist.value, songs)
    toaster.value.success(`Added ${pluralize(songs.length, 'song')} into "${playlist.value.name}."`)
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

const onPlaylistNameUpdated = (name: string) => {
  playlist.value.name = name
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
  overflow: hidden;

  a {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;

    span {
      pointer-events: none;
    }
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
