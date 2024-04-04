<template>
  <ContextMenuBase ref="base">
    <li @click="play">Play</li>
    <li @click="shuffle">Shuffle</li>
    <li @click="addToQueue">Add to Queue</li>
    <template v-if="canShowCollaboration">
      <li class="separator" />
      <li @click="showCollaborationModal">Collaborate…</li>
      <li class="separator" />
    </template>
    <li v-if="canEditPlaylist" @click="edit">Edit…</li>
    <li v-if="canEditPlaylist" @click="destroy">Delete</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { usePolicies, useContextMenu, useMessageToaster, useKoelPlus, useRouter } from '@/composables'
import { playbackService } from '@/services'
import { songStore, queueStore } from '@/stores'

const { base, ContextMenuBase, open, trigger } = useContextMenu()
const { go } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { isPlus } = useKoelPlus()
const { currentUserCan } = usePolicies()

const playlist = ref<Playlist>()

const canEditPlaylist = computed(() => currentUserCan.editPlaylist(playlist.value!))
const canShowCollaboration = computed(() => isPlus.value && !playlist.value?.is_smart)

const edit = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value!))
const destroy = () => trigger(() => eventBus.emit('PLAYLIST_DELETE', playlist.value!))

const play = () => trigger(async () => {
  const songs = await songStore.fetchForPlaylist(playlist.value!)

  if (songs.length) {
    playbackService.queueAndPlay(songs)
    go('queue')
  } else {
    toastWarning('The playlist is empty.')
  }
})

const shuffle = () => trigger(async () => {
  const songs = await songStore.fetchForPlaylist(playlist.value!)

  if (songs.length) {
    playbackService.queueAndPlay(songs, true)
    go('queue')
  } else {
    toastWarning('The playlist is empty.')
  }
})

const addToQueue = () => trigger(async () => {
  const songs = await songStore.fetchForPlaylist(playlist.value!)

  if (songs.length) {
    queueStore.queueAfterCurrent(songs)
    toastSuccess('Playlist added to queue.')
  } else {
    toastWarning('The playlist is empty.')
  }
})

const showCollaborationModal = () => trigger(() => eventBus.emit('MODAL_SHOW_PLAYLIST_COLLABORATION', playlist.value!))

eventBus.on('PLAYLIST_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _playlist) => {
  playlist.value = _playlist
  await open(pageY, pageX)
})
</script>
