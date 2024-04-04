<template>
  <dialog
    ref="dialog"
    class="text-k-text-primary border-0 p-0 rounded-md min-w-[calc(100vm_-_24px)] md:min-w-[460px]
    max-w-[calc(100vw_-_24px)] bg-k-bg-primary overflow-visible backdrop:bg-black/70"
    @cancel.prevent
  >
    <Component :is="modalNameToComponentMap[activeModalName]" v-if="activeModalName" @close="close" />
  </dialog>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, watch } from 'vue'
import { arrayify, eventBus, provideReadonly } from '@/utils'
import { ModalContextKey } from '@/symbols'

const modalNameToComponentMap = {
  'create-playlist-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue')),
  'edit-playlist-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistForm.vue')),
  'create-smart-playlist-form': defineAsyncComponent(() => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue')),
  'edit-smart-playlist-form': defineAsyncComponent(() => import('@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue')),
  'add-user-form': defineAsyncComponent(() => import('@/components/user/AddUserForm.vue')),
  'edit-user-form': defineAsyncComponent(() => import('@/components/user/EditUserForm.vue')),
  'invite-user-form': defineAsyncComponent(() => import('@/components/user/InviteUserForm.vue')),
  'edit-song-form': defineAsyncComponent(() => import('@/components/song/EditSongForm.vue')),
  'create-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistFolderForm.vue')),
  'edit-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue')),
  'playlist-collaboration': defineAsyncComponent(() => import('@/components/playlist/PlaylistCollaborationModal.vue')),
  'about-koel': defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue')),
  'koel-plus': defineAsyncComponent(() => import('@/components/koel-plus/KoelPlusModal.vue')),
  'equalizer': defineAsyncComponent(() => import('@/components/ui/equalizer/Equalizer.vue'))
}

type ModalName = keyof typeof modalNameToComponentMap

const dialog = ref<HTMLDialogElement>()
const activeModalName = ref<ModalName | null>(null)
const context = ref<Record<string, any>>({})

provideReadonly(ModalContextKey, context)

watch(activeModalName, name => name ? dialog.value?.showModal() : dialog.value?.close())

const close = () => {
  activeModalName.value = null
  context.value = {}
}

eventBus.on('MODAL_SHOW_ABOUT_KOEL', () => (activeModalName.value = 'about-koel'))
  .on('MODAL_SHOW_KOEL_PLUS', () => (activeModalName.value = 'koel-plus'))
  .on('MODAL_SHOW_ADD_USER_FORM', () => (activeModalName.value = 'add-user-form'))
  .on('MODAL_SHOW_INVITE_USER_FORM', () => (activeModalName.value = 'invite-user-form'))
  .on('MODAL_SHOW_CREATE_PLAYLIST_FORM', (folder, songs) => {
    context.value = {
      folder,
      songs: songs ? arrayify(songs) : []
    }

    activeModalName.value = 'create-playlist-form'
  })
  .on('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', folder => {
    context.value = { folder }
    activeModalName.value = 'create-smart-playlist-form'
  })
  .on('MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM', () => (activeModalName.value = 'create-playlist-folder-form'))
  .on('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist => {
    context.value = { playlist }
    activeModalName.value = playlist.is_smart ? 'edit-smart-playlist-form' : 'edit-playlist-form'
  })
  .on('MODAL_SHOW_EDIT_USER_FORM', user => {
    context.value = { user }
    activeModalName.value = 'edit-user-form'
  })
  .on('MODAL_SHOW_EDIT_SONG_FORM', (songs, initialTab: EditSongFormTabName = 'details') => {
    context.value = {
      initialTab,
      songs: arrayify(songs)
    }

    activeModalName.value = 'edit-song-form'
  })
  .on('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder => {
    context.value = { folder }
    activeModalName.value = 'edit-playlist-folder-form'
  })
  .on('MODAL_SHOW_PLAYLIST_COLLABORATION', playlist => {
    context.value = { playlist }
    activeModalName.value = 'playlist-collaboration'
  })
  .on('MODAL_SHOW_EQUALIZER', () => (activeModalName.value = 'equalizer'))
</script>

<style lang="postcss" scoped>
dialog {
  :deep(form), :deep(>div) {
    @apply relative;

    > header, > main, > footer {
      @apply px-6 py-5;
    }

    > footer {
      @apply mt-0 bg-black/10 border-t border-white/5 space-x-2;
    }

    > header {
      @apply flex bg-k-bg-secondary;

      h1 {
        @apply text-3xl leading-normal overflow-hidden text-ellipsis whitespace-nowrap;
      }
    }
  }
}
</style>
