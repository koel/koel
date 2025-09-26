<template>
  <ul>
    <template v-if="playable">
      <MenuItem @click="play">Play All</MenuItem>
      <MenuItem @click="shuffle">Shuffle All</MenuItem>
      <Separator />
    </template>
    <MenuItem>
      Add
      <template #subMenuItems>
        <MenuItem @click="createPlaylist">New Playlist…</MenuItem>
        <MenuItem @click="createSmartPlaylist">New Smart Playlist…</MenuItem>
      </template>
    </MenuItem>
    <Separator />
    <MenuItem @click="rename">Rename</MenuItem>
    <MenuItem @click="destroy">Delete</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { playlistStore } from '@/stores/playlistStore'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'

const props = defineProps<{ folder: PlaylistFolder }>()
const { folder } = toRefs(props)

const { MenuItem, Separator, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const playlistsInFolder = computed(() => folder.value ? playlistStore.byFolder(folder.value) : [])
const playable = computed(() => playlistsInFolder.value.length > 0)

const play = () => trigger(async () => {
  const songs = await playableStore.fetchForPlaylistFolder(folder.value!)

  if (songs.length) {
    playback().queueAndPlay(songs)
    go(url('queue'))
  } else {
    toastWarning('No songs available.')
  }
})

const shuffle = () => trigger(async () => {
  const songs = await playableStore.fetchForPlaylistFolder(folder.value!)

  if (songs.length) {
    playback().queueAndPlay(songs, true)
    go(url('queue'))
  } else {
    toastWarning('No songs available.')
  }
})

const createPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_PLAYLIST_FORM', folder.value!))
const createSmartPlaylist = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', folder.value!))
const rename = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder.value!))

const destroy = () => trigger(async () => {
  if (await showConfirmDialog(`Delete the playlist folder "${folder.value!.name}"?`)) {
    await playlistFolderStore.delete(folder.value!)
    toastSuccess(`Playlist folder "${folder.value!.name}" deleted.`)
  }
})
</script>
