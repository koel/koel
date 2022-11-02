<template>
  <dialog ref="dialog" class="text-primary bg-primary" @cancel.prevent>
    <Component :is="modalNameToComponentMap[activeModalName]" v-if="activeModalName" @close="close"/>
  </dialog>
</template>

<script lang="ts" setup>
import { ComponentPublicInstance, defineAsyncComponent, ref, watch } from 'vue'
import { arrayify, eventBus, provideReadonly } from '@/utils'
import { EditSongFormInitialTabKey, PlaylistFolderKey, PlaylistKey, SongsKey, UserKey } from '@/symbols'

const modalNameToComponentMap: Record<string, ComponentPublicInstance> = {
  'create-playlist-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue')),
  'edit-playlist-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistForm.vue')),
  'create-smart-playlist-form': defineAsyncComponent(() => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue')),
  'edit-smart-playlist-form': defineAsyncComponent(() => import('@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue')),
  'add-user-form': defineAsyncComponent(() => import('@/components/user/AddUserForm.vue')),
  'edit-user-form': defineAsyncComponent(() => import('@/components/user/EditUserForm.vue')),
  'edit-song-form': defineAsyncComponent(() => import('@/components/song/EditSongForm.vue')),
  'create-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistFolderForm.vue')),
  'edit-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue')),
  'about-koel': defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue')),
  'equalizer': defineAsyncComponent(() => import('@/components/ui/Equalizer.vue'))
}

type ModalName = keyof typeof modalNameToComponentMap

const dialog = ref<HTMLDialogElement>()
const activeModalName = ref<ModalName | null>(null)
const songsToEdit = ref<Song[]>()
const editSongFormInitialTab = ref<EditSongFormTabName>('details')
const userToEdit = ref<User>()
const playlistToEdit = ref<Playlist>()
const playlistFolderToEdit = ref<PlaylistFolder>()

provideReadonly(SongsKey, songsToEdit, false)
provideReadonly(EditSongFormInitialTabKey, editSongFormInitialTab)
provideReadonly(UserKey, userToEdit)
provideReadonly(PlaylistKey, playlistToEdit, false)

provideReadonly(PlaylistFolderKey, playlistFolderToEdit, true, (name: string) => {
  playlistFolderToEdit.value!.name = name
})

watch(activeModalName, name => name ? dialog.value?.showModal() : dialog.value?.close())

const close = () => (activeModalName.value = null)

eventBus.on({
  MODAL_SHOW_ABOUT_KOEL: () => (activeModalName.value = 'about-koel'),
  MODAL_SHOW_ADD_USER_FORM: () => (activeModalName.value = 'add-user-form'),
  MODAL_SHOW_CREATE_PLAYLIST_FORM: () => (activeModalName.value = 'create-playlist-form'),
  MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM: () => (activeModalName.value = 'create-smart-playlist-form'),
  MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM: () => (activeModalName.value = 'create-playlist-folder-form'),

  MODAL_SHOW_EDIT_PLAYLIST_FORM: (playlist: Playlist) => {
    playlistToEdit.value = playlist
    activeModalName.value = playlist.is_smart ? 'edit-smart-playlist-form' : 'edit-playlist-form'
  },

  MODAL_SHOW_EDIT_USER_FORM: (user: User) => {
    userToEdit.value = user
    activeModalName.value = 'edit-user-form'
  },

  MODAL_SHOW_EDIT_SONG_FORM: (songs: Song | Song[], initialTab: EditSongFormTabName = 'details') => {
    songsToEdit.value = arrayify(songs)
    editSongFormInitialTab.value = initialTab
    activeModalName.value = 'edit-song-form'
  },

  MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM: (folder: PlaylistFolder) => {
    playlistFolderToEdit.value = folder
    activeModalName.value = 'edit-playlist-folder-form'
  },

  MODAL_SHOW_EQUALIZER: () => (activeModalName.value = 'equalizer')
})
</script>

<style lang="scss" scoped>
dialog {
  border: 0;
  padding: 0;
  border-radius: 4px;
  min-width: 460px;
  max-width: calc(100vw - 24px);

  @media screen and (max-width: 768px) {
    min-width: calc(100vw - 24px);
  }

  &::backdrop {
    background: rgba(0, 0, 0, 0.7);
  }

  :deep(form) {
    position: relative;

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

    > footer {
      button + button {
        margin-left: .5rem;
      }
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
