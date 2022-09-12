<template>
  <div class="modal-wrapper" :class="{ overlay: showingModalName }">
    <CreatePlaylistForm v-if="showingModalName === 'create-playlist-form'" @close="close"/>
    <EditPlaylistForm v-else-if="showingModalName === 'edit-playlist-form'" @close="close"/>
    <CreateSmartPlaylistForm v-if="showingModalName === 'create-smart-playlist-form'" @close="close"/>
    <EditSmartPlaylistForm v-if="showingModalName === 'edit-smart-playlist-form'" @close="close"/>
    <AddUserForm v-if="showingModalName === 'add-user-form'" @close="close"/>
    <EditUserForm v-if="showingModalName === 'edit-user-form'" @close="close"/>
    <EditSongForm v-if="showingModalName === 'edit-song-form'" @close="close"/>
    <CreatePlaylistFolderForm v-if="showingModalName === 'create-playlist-folder-form'" @close="close"/>
    <EditPlaylistFolderForm v-if="showingModalName === 'edit-playlist-folder-form'" @close="close"/>
    <AboutKoel v-if="showingModalName === 'about-koel'" @close="close"/>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref } from 'vue'
import { arrayify, eventBus, provideReadonly } from '@/utils'
import { EditSongFormInitialTabKey, PlaylistFolderKey, PlaylistKey, SongsKey, UserKey } from '@/symbols'

declare type ModalName =
  | 'create-playlist-form'
  | 'edit-playlist-form'
  | 'create-smart-playlist-form'
  | 'edit-smart-playlist-form'
  | 'add-user-form'
  | 'edit-user-form'
  | 'edit-song-form'
  | 'create-playlist-folder-form'
  | 'edit-playlist-folder-form'
  | 'about-koel'

const CreatePlaylistForm = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue'))
const EditPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/EditPlaylistForm.vue'))
const CreateSmartPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue'))
const EditSmartPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue'))
const AddUserForm = defineAsyncComponent(() => import('@/components/user/AddUserForm.vue'))
const EditUserForm = defineAsyncComponent(() => import('@/components/user/EditUserForm.vue'))
const EditSongForm = defineAsyncComponent(() => import('@/components/song/EditSongForm.vue'))
const CreatePlaylistFolderForm = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistFolderForm.vue'))
const EditPlaylistFolderForm = defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue'))
const AboutKoel = defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue'))

const showingModalName = ref<ModalName | null>(null)

const close = () => (showingModalName.value = null)

const playlistToEdit = ref<Playlist>()
const userToEdit = ref<User>()
const songsToEdit = ref<Song[]>()
const playlistFolderToEdit = ref<PlaylistFolder>()
const editSongFormInitialTab = ref<EditSongFormTabName>('details')

provideReadonly(PlaylistKey, playlistToEdit, false)
provideReadonly(UserKey, userToEdit)

provideReadonly(PlaylistFolderKey, playlistFolderToEdit, true, (name: string) => {
  playlistFolderToEdit.value!.name = name
})

provideReadonly(SongsKey, songsToEdit, false)
provideReadonly(EditSongFormInitialTabKey, editSongFormInitialTab)

eventBus.on({
  MODAL_SHOW_ABOUT_KOEL: () => (showingModalName.value = 'about-koel'),
  MODAL_SHOW_ADD_USER_FORM: () => (showingModalName.value = 'add-user-form'),
  MODAL_SHOW_CREATE_PLAYLIST_FORM: () => (showingModalName.value = 'create-playlist-form'),
  MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM: () => (showingModalName.value = 'create-smart-playlist-form'),
  MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM: () => (showingModalName.value = 'create-playlist-folder-form'),

  MODAL_SHOW_EDIT_PLAYLIST_FORM: (playlist: Playlist) => {
    playlistToEdit.value = playlist
    showingModalName.value = playlist.is_smart ? 'edit-smart-playlist-form' : 'edit-playlist-form'
  },

  MODAL_SHOW_EDIT_USER_FORM: (user: User) => {
    userToEdit.value = user
    showingModalName.value = 'edit-user-form'
  },

  MODAL_SHOW_EDIT_SONG_FORM: (songs: Song | Song[], initialTab: EditSongFormTabName = 'details') => {
    songsToEdit.value = arrayify(songs)
    editSongFormInitialTab.value = initialTab
    showingModalName.value = 'edit-song-form'
  },

  MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM: (folder: PlaylistFolder) => {
    playlistFolderToEdit.value = folder
    showingModalName.value = 'edit-playlist-folder-form'
  }
})
</script>

<style lang="scss">
.modal-wrapper {
  form {
    position: relative;
    min-width: 460px;
    max-width: calc(100% - 24px);
    background-color: var(--color-bg-primary);
    border-radius: 4px;

    @media only screen and (max-width: 667px) {
      min-width: calc(100% - 24px);
    }

    > header, > main, > footer {
      padding: 1.2rem;
    }

    > footer {
      margin-top: 0;
    }

    [type=text], [type=number], [type=email], [type=password], [type=url], [type=date], textarea, select {
      width: 100%;
      max-width: 100%;
      height: 32px;
    }

    .warning {
      color: var(--color-red);
    }

    textarea {
      min-height: 192px;
    }

    > header {
      display: flex;
      background: var(--color-bg-secondary);

      h1 {
        font-size: 1.8rem;
        line-height: 2.2rem;
        margin-bottom: .3rem;
      }
    }
  }
}
</style>
