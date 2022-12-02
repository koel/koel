<template>
  <dialog ref="dialog" class="text-primary bg-primary" @cancel.prevent>
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
  'edit-song-form': defineAsyncComponent(() => import('@/components/song/EditSongForm.vue')),
  'create-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistFolderForm.vue')),
  'edit-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue')),
  'about-koel': defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue')),
  'equalizer': defineAsyncComponent(() => import('@/components/ui/Equalizer.vue'))
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
  .on('MODAL_SHOW_ADD_USER_FORM', () => (activeModalName.value = 'add-user-form'))
  .on('MODAL_SHOW_CREATE_PLAYLIST_FORM', folder => {
    context.value = { folder }
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
  .on('MODAL_SHOW_EQUALIZER', () => (activeModalName.value = 'equalizer'))
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
