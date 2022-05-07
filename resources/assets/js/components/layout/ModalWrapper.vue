<template>
  <div class="modal-wrapper" :class="{ overlay: showingModalName }">
    <CreateSmartPlaylistForm v-if="showingModalName === 'create-smart-playlist-form'" @close="close"/>
    <EditSmartPlaylistForm
      v-if="showingModalName === 'edit-smart-playlist-form'"
      :playlist="boundData.playlist"
      @close="close"
    />
    <AddUserForm v-if="showingModalName === 'add-user-form'" @close="close"/>
    <EditUserForm v-if="showingModalName === 'edit-user-form'" :user="boundData.user" @close="close"/>
    <EditSongForm
      v-if="showingModalName === 'edit-song-form'"
      :initialTab="boundData.initialTab"
      :songs="boundData.songs"
      @close="close"
    />
    <AboutKoel v-if="showingModalName === 'about-koel'" @close="close"/>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref } from 'vue'
import { eventBus, arrayify } from '@/utils'

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
  | 'about-koel'

const CreateSmartPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistCreateForm.vue'))
const EditSmartPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistEditForm.vue'))
const AddUserForm = defineAsyncComponent(() => import('@/components/user/UserAddForm.vue'))
const EditUserForm = defineAsyncComponent(() => import('@/components/user/UserEditForm.vue'))
const EditSongForm = defineAsyncComponent(() => import('@/components/song/SongEditForm.vue'))
const AboutKoel = defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue'))

const showingModalName = ref<ModalName | null>(null)
const boundData = ref<ModalWrapperBoundData>({})

const close = () => {
  showingModalName.value = null
  boundData.value = {}
}

eventBus.on({
  'MODAL_SHOW_ABOUT_KOEL': () => (showingModalName.value = 'about-koel'),
  'MODAL_SHOW_ADD_USER_FORM': () => (showingModalName.value = 'add-user-form'),
  'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM': () => (showingModalName.value = 'create-smart-playlist-form'),

  MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM (playlist: Playlist) {
    boundData.value.playlist = playlist
    showingModalName.value = 'edit-smart-playlist-form'
  },

  MODAL_SHOW_EDIT_USER_FORM (user: User) {
    boundData.value.user = user
    showingModalName.value = 'edit-user-form'
  },

  MODAL_SHOW_EDIT_SONG_FORM (songs: Song | Song[], initialTab = 'details') {
    boundData.value.songs = arrayify(songs)
    boundData.value.initialTab = initialTab
    showingModalName.value = 'edit-song-form'
  }
})
</script>
