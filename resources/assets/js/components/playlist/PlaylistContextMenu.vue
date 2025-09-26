<template>
  <ul>
    <MenuItem @click="play">Play</MenuItem>
    <MenuItem @click="shuffle">Shuffle</MenuItem>
    <MenuItem @click="addToQueue">Add to Queue</MenuItem>
    <MenuItem>
      Share
      <template #subMenuItems>
        <MenuItem @click="showEmbedModal">Embed…</MenuItem>
        <MenuItem v-if="canShowCollaboration" @click="showCollaborationModal">Collaborate…</MenuItem>
      </template>
    </MenuItem>
    <template v-if="allowDownload">
      <Separator />
      <MenuItem @click="download">Download</MenuItem>
    </template>
    <template v-if="canEditPlaylist">
      <Separator />
      <MenuItem @click="edit">Edit…</MenuItem>
      <MenuItem @click="destroy">Delete</MenuItem>
    </template>
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { usePolicies } from '@/composables/usePolicies'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { commonStore } from '@/stores/commonStore'
import { downloadService } from '@/services/downloadService'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { MenuItem, Separator, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { isPlus } = useKoelPlus()
const { currentUserCan } = usePolicies()
const { showConfirmDialog } = useDialogBox()

const allowDownload = toRef(commonStore.state, 'allows_download')

const canEditPlaylist = computed(() => currentUserCan.editPlaylist(playlist.value))
const canShowCollaboration = computed(() => isPlus.value && !playlist.value?.is_smart)

const edit = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value))

const destroy = () => trigger(async () => {
  if (await showConfirmDialog(`Delete the playlist "${playlist.value.name}"?`)) {
    await playlistStore.delete(playlist.value)
    toastSuccess(`Playlist "${playlist.value.name}" deleted.`)
    eventBus.emit('PLAYLIST_DELETED', playlist.value)
  }
})

const download = () => trigger(() => downloadService.fromPlaylist(playlist.value))

const play = () => trigger(async () => {
  const songs = await playableStore.fetchForPlaylist(playlist.value)

  if (songs.length) {
    playback().queueAndPlay(songs)
    go(url('queue'))
  } else {
    toastWarning('The playlist is empty.')
  }
})

const shuffle = () => trigger(async () => {
  const songs = await playableStore.fetchForPlaylist(playlist.value)

  if (songs.length) {
    playback().queueAndPlay(songs, true)
    go(url('queue'))
  } else {
    toastWarning('The playlist is empty.')
  }
})

const addToQueue = () => trigger(async () => {
  const songs = await playableStore.fetchForPlaylist(playlist.value)

  if (songs.length) {
    queueStore.queueAfterCurrent(songs)
    toastSuccess('Playlist added to queue.')
  } else {
    toastWarning('The playlist is empty.')
  }
})

const showCollaborationModal = () => trigger(() => eventBus.emit('MODAL_SHOW_PLAYLIST_COLLABORATION', playlist.value))
const showEmbedModal = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_EMBED_FORM', playlist.value))
</script>
