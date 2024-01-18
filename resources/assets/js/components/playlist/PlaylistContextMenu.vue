<template>
  <ContextMenuBase ref="base">
    <li @click="play">Play</li>
    <li @click="shuffle">Shuffle</li>
    <li @click="addToQueue">Add to Queue</li>
    <template v-if="canInviteCollaborators">
      <li class="separator"></li>
      <li @click="inviteCollaborators">Invite Collaborators</li>
      <li class="separator"></li>
    </template>
    <li v-if="ownedByCurrentUser" @click="edit">Edit…</li>
    <li v-if="ownedByCurrentUser" @click="destroy">Delete</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { copyText, eventBus } from '@/utils'
import { useAuthorization, useContextMenu, useMessageToaster, useKoelPlus, useRouter } from '@/composables'
import { playbackService, playlistCollaborationService } from '@/services'
import { songStore, queueStore } from '@/stores'

const { base, ContextMenuBase, open, trigger } = useContextMenu()
const { go } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { isPlus } = useKoelPlus()
const { currentUser } = useAuthorization()

const playlist = ref<Playlist>()

const ownedByCurrentUser = computed(() => playlist.value?.user_id === currentUser.value?.id)
const canInviteCollaborators = computed(() => ownedByCurrentUser.value && isPlus.value && !playlist.value?.is_smart)

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

const inviteCollaborators = () => trigger(async () => {
  const link = await playlistCollaborationService.createInviteLink(playlist.value!)
  await copyText(link)
  toastSuccess('Link copied to clipboard. Share it with your friends!')
})

eventBus.on('PLAYLIST_CONTEXT_MENU_REQUESTED', async (event, _playlist) => {
  playlist.value = _playlist
  await open(event.pageY, event.pageX)
})
</script>
