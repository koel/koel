<template>
  <div class="modal-wrapper" :class="{ overlay: showingModalName }">
    <CreateSmartPlaylistForm v-if="showingModalName === 'create-smart-playlist-form'" @close="close"/>
    <EditSmartPlaylistForm
      v-if="showingModalName === 'edit-smart-playlist-form'"
      @close="close"
      :playlist="boundData.playlist"
    />
    <AddUserForm v-if="showingModalName === 'add-user-form'" @close="close"/>
    <EditUserForm v-if="showingModalName === 'edit-user-form'" :user="boundData.user" @close="close"/>
    <EditSongForm
      :songs="boundData.songs"
      :initialTab="boundData.initialTab"
      @close="close"
      v-if="showingModalName === 'edit-song-form'"
    />
    <AboutDialog v-if="showingModalName === 'about-dialog'" @close="close"/>
  </div>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils'
import { defineAsyncComponent, ref } from 'vue'

interface ModalWrapperBoundData {
  playlist?: Playlist
  user?: User
  songs?: Song[]
  initialTab?: string
}

declare type ModalName =
  | 'create-smart-playlist-form'
  | 'edit-smart-playlist-form'
  | 'add-user-form'
  | 'edit-user-form'
  | 'edit-song-form'
  | 'about-dialog'

const CreateSmartPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistCreateForm.vue'))
const EditSmartPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistEditForm.vue'))
const AddUserForm = defineAsyncComponent(() => import('@/components/user/add-form.vue'))
const EditUserForm = defineAsyncComponent(() => import('@/components/user/edit-form.vue'))
const EditSongForm = defineAsyncComponent(() => import('@/components/song/edit-form.vue'))
const AboutDialog = defineAsyncComponent(() => import('@/components/meta/about-dialog.vue'))

const showingModalName = ref<ModalName | null>(null)
const boundData = ref<ModalWrapperBoundData>({})

const close = () => {
  showingModalName.value = null
  boundData.value = {}
}

eventBus.on({
  'MODAL_SHOW_ABOUT_DIALOG': () => (showingModalName.value = 'about-dialog'),
  'MODAL_SHOW_ADD_USER_FORM': () => (showingModalName.value = 'add-user-form'),
  'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM': () => (showingModalName.value = 'create-smart-playlist-form'),

  'MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM': (playlist: Playlist) => {
    boundData.value.playlist = playlist
    showingModalName.value = 'edit-smart-playlist-form'
  },

  'MODAL_SHOW_EDIT_USER_FORM': (user: User) => {
    boundData.value.user = user
    showingModalName.value = 'edit-user-form'
  },

  'MODAL_SHOW_EDIT_SONG_FORM': (songs: Song[], initialTab: string = 'details'): void => {
    boundData.value.songs = songs
    boundData.value.initialTab = initialTab
    showingModalName.value = 'edit-song-form'
  }
})
</script>
