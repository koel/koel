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
import { defineAsyncComponent } from '@/utils/helpers'
import { playlistStore } from '@/stores/playlistStore'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'

const props = defineProps<{ folder: PlaylistFolder }>()
const { folder } = toRefs(props)

const CreatePlaylistForm = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue'))
const CreateSmartPlaylistForm = defineAsyncComponent(
  () => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue'),
)
const EditPlaylistFolderForm = defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue'))

const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const playlistsInFolder = computed(() => (folder.value ? playlistStore.byFolder(folder.value) : []))
const playable = computed(() => playlistsInFolder.value.length > 0)

const play = () =>
  trigger(async () => {
    const songs = await playableStore.fetchForPlaylistFolder(folder.value!)

    if (songs.length) {
      playback().queueAndPlay(songs)
      go(url('queue'))
    } else {
      toastWarning('No songs available.')
    }
  })

const shuffle = () =>
  trigger(async () => {
    const songs = await playableStore.fetchForPlaylistFolder(folder.value!)

    if (songs.length) {
      playback().queueAndPlay(songs, true)
      go(url('queue'))
    } else {
      toastWarning('No songs available.')
    }
  })

const createPlaylist = () =>
  trigger(() => openModal<'CREATE_PLAYLIST_FORM'>(CreatePlaylistForm, { folder: folder.value!, playables: [] }))
const createSmartPlaylist = () =>
  trigger(() => openModal<'CREATE_SMART_PLAYLIST_FORM'>(CreateSmartPlaylistForm, { folder: folder.value! }))
const rename = () =>
  trigger(() => openModal<'EDIT_PLAYLIST_FOLDER_FORM'>(EditPlaylistFolderForm, { folder: folder.value! }))

const destroy = () =>
  trigger(async () => {
    if (await showConfirmDialog(`Delete the playlist folder "${folder.value!.name}"?`)) {
      await playlistFolderStore.delete(folder.value!)
      toastSuccess(`Playlist folder "${folder.value!.name}" deleted.`)
    }
  })
</script>
