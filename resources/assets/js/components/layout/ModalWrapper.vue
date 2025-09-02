<template>
  <dialog
    ref="dialog"
    class="text-k-text-primary min-w-full md:min-w-[480px] border-0 p-0 rounded-md overflow-visible bg-k-bg-primary backdrop:bg-black/70"
    @close.prevent
  >
    <Component
      :is="modalNameToComponentMap[activeModalName]"
      v-if="activeModalName"
      @close="close"
    />
  </dialog>
</template>

<script lang="ts" setup>
import { provide, ref, watch } from 'vue'
import { arrayify, defineAsyncComponent } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { ModalContextKey } from '@/symbols'

const modalNameToComponentMap = {
  'about-koel': defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue')),
  'add-podcast-form': defineAsyncComponent(() => import('@/components/podcast/AddPodcastForm.vue')),
  'add-radio-station-form': defineAsyncComponent(() => import('@/components/radio/AddRadioStationForm.vue')),
  'add-user-form': defineAsyncComponent(() => import('@/components/user/AddUserForm.vue')),
  'create-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistFolderForm.vue')),
  'create-playlist-form': defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue')),
  'create-smart-playlist-form': defineAsyncComponent(() => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue')),
  'edit-album-form': defineAsyncComponent(() => import('@/components/album/EditAlbumForm.vue')),
  'edit-artist-form': defineAsyncComponent(() => import('@/components/artist/EditArtistForm.vue')),
  'edit-playlist-folder-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue')),
  'edit-playlist-form': defineAsyncComponent(() => import('@/components/playlist/EditPlaylistForm.vue')),
  'edit-radio-station-form': defineAsyncComponent(() => import('@/components/radio/EditRadioStationForm.vue')),
  'edit-smart-playlist-form': defineAsyncComponent(() => import('@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue')),
  'edit-song-form': defineAsyncComponent(() => import('@/components/playable/EditSongForm.vue')),
  'edit-user-form': defineAsyncComponent(() => import('@/components/user/EditUserForm.vue')),
  'equalizer': defineAsyncComponent(() => import('@/components/ui/equalizer/Equalizer.vue')),
  'invite-user-form': defineAsyncComponent(() => import('@/components/user/InviteUserForm.vue')),
  'koel-plus': defineAsyncComponent(() => import('@/components/koel-plus/KoelPlusModal.vue')),
  'playlist-collaboration': defineAsyncComponent(() => import('@/components/playlist/PlaylistCollaborationModal.vue')),
}

type ModalName = keyof typeof modalNameToComponentMap

const dialog = ref<HTMLDialogElement>()
const activeModalName = ref<ModalName | null>(null)
const context = ref<Record<string, any>>({})

provide(ModalContextKey, context)

watch(activeModalName, name => name ? dialog.value?.showModal() : dialog.value?.close())

const close = () => {
  activeModalName.value = null
  context.value = {}
}

const showModal = (name: ModalName, ctx: Record<string, any> = {}) => {
  context.value = ctx
  activeModalName.value = name
}

eventBus.on('MODAL_SHOW_ABOUT_KOEL', () => showModal('about-koel'))
  .on('MODAL_SHOW_KOEL_PLUS', () => showModal('koel-plus'))
  .on('MODAL_SHOW_ADD_USER_FORM', () => showModal('add-user-form'))
  .on('MODAL_SHOW_INVITE_USER_FORM', () => showModal('invite-user-form'))
  .on('MODAL_SHOW_CREATE_PLAYLIST_FORM', (folder, playables) => showModal('create-playlist-form', {
    folder,
    playables: playables ? arrayify(playables) : [],
  }))
  .on('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', folder => showModal('create-smart-playlist-form', { folder }))
  .on('MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM', () => showModal('create-playlist-folder-form'))
  .on('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist => showModal(
    playlist.is_smart ? 'edit-smart-playlist-form' : 'edit-playlist-form',
    { playlist },
  ))
  .on('MODAL_SHOW_EDIT_ALBUM_FORM', album => showModal('edit-album-form', { album }))
  .on('MODAL_SHOW_EDIT_ARTIST_FORM', artist => showModal('edit-artist-form', { artist }))
  .on('MODAL_SHOW_EDIT_USER_FORM', user => showModal('edit-user-form', { user }))
  .on('MODAL_SHOW_EDIT_SONG_FORM', (songs, initialTab: EditSongFormTabName = 'details') => showModal('edit-song-form', {
    initialTab,
    songs: arrayify(songs),
  }))
  .on('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder => showModal('edit-playlist-folder-form', { folder }))
  .on('MODAL_SHOW_PLAYLIST_COLLABORATION', playlist => showModal('playlist-collaboration', { playlist }))
  .on('MODAL_SHOW_ADD_PODCAST_FORM', () => showModal('add-podcast-form'))
  .on('MODAL_SHOW_ADD_RADIO_STATION_FORM', () => showModal('add-radio-station-form'))
  .on('MODAL_SHOW_EDIT_RADIO_STATION_FORM', station => showModal('edit-radio-station-form', { station }))
  .on('MODAL_SHOW_EQUALIZER', () => showModal('equalizer'))
</script>

<style lang="postcss" scoped>
dialog {
  :deep(form),
  :deep(> div) {
    @apply relative;

    > header,
    > main,
    > footer {
      @apply px-6 py-5;
    }

    > header {
      @apply flex bg-k-bg-secondary rounded-t-md;

      h1 {
        @apply text-3xl leading-normal overflow-hidden text-ellipsis whitespace-nowrap;
      }
    }

    > footer {
      @apply mt-0 bg-black/10 border-t border-white/5 space-x-2 rounded-b-md;
    }
  }
}
</style>
