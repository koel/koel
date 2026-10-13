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
        <MenuItem @click="createFolder">New Folder…</MenuItem>
      </template>
    </MenuItem>
    <Separator />
    <MenuItem @click="edit">Edit…</MenuItem>
    <MenuItem @click="destroy">Delete</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { playableStore } from '@/stores/playableStore'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { usePlayCollection } from '@/composables/usePlayCollection'

const props = defineProps<{ folder: PlaylistFolder }>()
const { folder } = toRefs(props)

const CreatePlaylistForm = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue'))
const CreateSmartPlaylistForm = defineAsyncComponent(
  () => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue'),
)
const CreatePlaylistFolderForm = defineAsyncComponent(
  () => import('@/components/playlist/CreatePlaylistFolderForm.vue'),
)
const EditPlaylistFolderForm = defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue'))

const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const playlistsInFolderTree = computed(() => playlistFolderStore.playlistsInTree(folder.value))
const playable = computed(() => playlistsInFolderTree.value.length > 0)

const { playCollection } = usePlayCollection()

const playSongs = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchForPlaylists(playlistsInFolderTree.value), {
      shuffle,
      emptyMessage: 'No songs available.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const createPlaylist = () =>
  trigger(() => openModal<'CREATE_PLAYLIST_FORM'>(CreatePlaylistForm, { folder: folder.value!, playables: [] }))
const createSmartPlaylist = () =>
  trigger(() => openModal<'CREATE_SMART_PLAYLIST_FORM'>(CreateSmartPlaylistForm, { folder: folder.value! }))
const createFolder = () =>
  trigger(() => openModal<'CREATE_PLAYLIST_FOLDER_FORM'>(CreatePlaylistFolderForm, { parent: folder.value }))
const edit = () =>
  trigger(() => openModal<'EDIT_PLAYLIST_FOLDER_FORM'>(EditPlaylistFolderForm, { folder: folder.value! }))

const destroy = () =>
  trigger(async () => {
    if (
      await showConfirmDialog(
        `Delete the playlist folder "${folder.value.name}"? Its playlists and subfolders will be kept.`,
      )
    ) {
      await playlistFolderStore.delete(folder.value)
      toastSuccess(`Playlist folder "${folder.value.name}" deleted.`)
    }
  })
</script>
