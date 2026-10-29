<template>
  <MenuItem v-if="allowEdit" @click="openEditForm">Edit…</MenuItem>
  <MenuItem v-if="downloadable" @click="download">Download</MenuItem>
  <MenuItem v-if="canToggleOffline" @click="toggleOffline">
    {{ allCached ? 'Remove Offline Versions' : 'Make Available Offline' }}
  </MenuItem>

  <template v-if="canBeRemovedFromPlaylist">
    <Separator />
    <MenuItem @click="removePlayablesFromPlaylist">Remove from Playlist</MenuItem>
  </template>

  <template v-if="allowEdit">
    <Separator />
    <MenuItem @click="deleteFromFilesystem">Delete from Filesystem</MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { pluralize } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { logger } from '@/utils/logger'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { playlistStore } from '@/stores/playlistStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useDialogBox } from '@/composables/useDialogBox'
import { useDownload } from '@/composables/useDownload'
import { getHttpErrorMessage } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { usePolicies } from '@/composables/usePolicies'
import { useRouter } from '@/composables/useRouter'

const props = defineProps<{ playables: Playable[] }>()

const EditSongForm = defineAsyncComponent(() => import('@/components/playable/EditSongForm.vue'))

const { MenuItem, Separator, trigger } = useContextMenu()
const { toastPromise } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { openModal } = useModal()
const { getRouteParam, isCurrentScreen } = useRouter()
const { currentUserCan } = usePolicies()
const { removeFromPlaylist } = usePlaylistContentManagement()
const { fromPlayables } = useDownload()
const { swReady, togglePlayablesOffline, isCached } = useOfflinePlayback()

const onlySongs = computed(() => getPlayableCollectionContentType(props.playables) === 'songs')
const allowEdit = computed(() => onlySongs.value && currentUserCan.editSong(props.playables as Song[]))

const downloadable = computed(() => {
  if (!commonStore.state.allows_download) {
    return false
  }

  return props.playables.length === 1 || commonStore.state.supports_batch_downloading
})

const canToggleOffline = computed(() => onlySongs.value && swReady.value)
const allCached = computed(() => props.playables.every(playable => isCached(playable)))

const canBeRemovedFromPlaylist = computed(() => {
  if (!isCurrentScreen('Playlist')) {
    return false
  }

  const playlist = playlistStore.byId(getRouteParam('id')!)

  return playlist && !playlist.is_smart
})

const openEditForm = () =>
  trigger(() => openModal<'EDIT_SONG_FORM'>(EditSongForm, { songs: props.playables as Song[], initialTab: 'details' }))

const download = () => trigger(() => fromPlayables(props.playables))

const toggleOffline = () => trigger(() => togglePlayablesOffline(props.playables))

const removePlayablesFromPlaylist = () =>
  trigger(async () => {
    const playlist = playlistStore.byId(getRouteParam('id'))

    if (!playlist) {
      return
    }

    await removeFromPlaylist(playlist, props.playables)
  })

const deleteFromFilesystem = () =>
  trigger(async () => {
    if (!(await showConfirmDialog('Delete selected playable(s) from the filesystem? This action is NOT reversible!'))) {
      return
    }

    const deleting = playableStore.deleteSongsFromFilesystem(props.playables as Song[])

    toastPromise(deleting, {
      loading: 'Deleting…',
      success: `Deleted ${pluralize(props.playables, 'song')} from the filesystem.`,
      error: getHttpErrorMessage,
    })

    try {
      await deleting
      eventBus.emit('SONGS_DELETED', props.playables as Song[])
    } catch (error: unknown) {
      logger.error(error)
    }
  })
</script>
