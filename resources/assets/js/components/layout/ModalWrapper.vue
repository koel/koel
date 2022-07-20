<template>
  <div class="modal-wrapper" :class="{ overlay: showingModalName }">
    <CreateSmartPlaylistForm v-if="showingModalName === 'create-smart-playlist-form'" @close="close"/>
    <EditSmartPlaylistForm v-if="showingModalName === 'edit-smart-playlist-form'" @close="close"/>
    <AddUserForm v-if="showingModalName === 'add-user-form'" @close="close"/>
    <EditUserForm v-if="showingModalName === 'edit-user-form'" @close="close"/>
    <EditSongForm v-if="showingModalName === 'edit-song-form'" @close="close"/>
    <AboutKoel v-if="showingModalName === 'about-koel'" @close="close"/>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref } from 'vue'
import { arrayify, eventBus, provideReadonly } from '@/utils'
import { EditSongFormInitialTabKey, PlaylistKey, SongsKey, UserKey } from '@/symbols'

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

const close = () => (showingModalName.value = null)

const playlistToEdit = ref<Playlist>()
const userToEdit = ref<User>()
const songsToEdit = ref<Song[]>()
const editSongFormInitialTab = ref<EditSongFormTabName>('details')

provideReadonly(PlaylistKey, playlistToEdit, false)
provideReadonly(UserKey, userToEdit)
provideReadonly(SongsKey, songsToEdit, false)
provideReadonly(EditSongFormInitialTabKey, editSongFormInitialTab)

eventBus.on({
  'MODAL_SHOW_ABOUT_KOEL': () => (showingModalName.value = 'about-koel'),
  'MODAL_SHOW_ADD_USER_FORM': () => (showingModalName.value = 'add-user-form'),
  'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM': () => (showingModalName.value = 'create-smart-playlist-form'),

  'MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM': (playlist: Playlist) => {
    playlistToEdit.value = playlist
    showingModalName.value = 'edit-smart-playlist-form'
  },

  'MODAL_SHOW_EDIT_USER_FORM': (user: User) => {
    userToEdit.value = user
    showingModalName.value = 'edit-user-form'
  },

  'MODAL_SHOW_EDIT_SONG_FORM': (songs: Song | Song[], initialTab: EditSongFormTabName = 'details') => {
    songsToEdit.value = arrayify(songs)
    editSongFormInitialTab.value = initialTab
    showingModalName.value = 'edit-song-form'
  }
})
</script>
