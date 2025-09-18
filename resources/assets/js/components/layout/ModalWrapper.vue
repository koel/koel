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
import type { Modals } from '@/config/modals'

const modalNameToComponentMap: Record<keyof Modals, Closure> = {
  ABOUT_KOEL: defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue')),
  ADD_PODCAST_FORM: defineAsyncComponent(() => import('@/components/podcast/AddPodcastForm.vue')),
  ADD_RADIO_STATION_FORM: defineAsyncComponent(() => import('@/components/radio/AddRadioStationForm.vue')),
  ADD_USER_FORM: defineAsyncComponent(() => import('@/components/user/AddUserForm.vue')),
  CREATE_EMBED_FORM: defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue')),
  CREATE_PLAYLIST_FOLDER_FORM: defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistFolderForm.vue')),
  CREATE_PLAYLIST_FORM: defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue')),
  CREATE_SMART_PLAYLIST_FORM: defineAsyncComponent(() => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue')),
  EDIT_ALBUM_FORM: defineAsyncComponent(() => import('@/components/album/EditAlbumForm.vue')),
  EDIT_ARTIST_FORM: defineAsyncComponent(() => import('@/components/artist/EditArtistForm.vue')),
  EDIT_PLAYLIST_FOLDER_FORM: defineAsyncComponent(() => import('@/components/playlist/EditPlaylistFolderForm.vue')),
  EDIT_PLAYLIST_FORM: defineAsyncComponent(() => import('@/components/playlist/EditPlaylistForm.vue')),
  EDIT_RADIO_STATION_FORM: defineAsyncComponent(() => import('@/components/radio/EditRadioStationForm.vue')),
  EDIT_SMART_PLAYLIST_FORM: defineAsyncComponent(() => import('@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue')),
  EDIT_SONG_FORM: defineAsyncComponent(() => import('@/components/playable/EditSongForm.vue')),
  EDIT_USER_FORM: defineAsyncComponent(() => import('@/components/user/EditUserForm.vue')),
  EQUALIZER: defineAsyncComponent(() => import('@/components/ui/equalizer/Equalizer.vue')),
  INVITE_USER_FORM: defineAsyncComponent(() => import('@/components/user/InviteUserForm.vue')),
  KOEL_PLUS: defineAsyncComponent(() => import('@/components/koel-plus/KoelPlusModal.vue')),
  PLAYLIST_COLLABORATION: defineAsyncComponent(() => import('@/components/playlist/PlaylistCollaborationModal.vue')),
}

const dialog = ref<HTMLDialogElement>()
const activeModalName = ref<keyof Modals | null>(null)
const context = ref<Record<string, any>>({})

provide(ModalContextKey, context)

watch(activeModalName, name => name ? dialog.value?.showModal() : dialog.value?.close())

const close = () => {
  activeModalName.value = null
  context.value = {}
}

const showModal = <N extends keyof Modals> (
  name: N,
  ...args: [Modals[N]] extends [never] ? [] : [ctx: Modals[N]]
) => {
  context.value = (args[0] ?? {}) as Modals[N]
  activeModalName.value = name
}

eventBus.on('MODAL_SHOW_ABOUT_KOEL', () => showModal('ABOUT_KOEL'))
  .on('MODAL_SHOW_KOEL_PLUS', () => showModal('KOEL_PLUS'))
  .on('MODAL_SHOW_ADD_USER_FORM', () => showModal('ADD_USER_FORM'))
  .on('MODAL_SHOW_INVITE_USER_FORM', () => showModal('INVITE_USER_FORM'))
  .on('MODAL_SHOW_CREATE_EMBED_FORM', embeddable => showModal('CREATE_EMBED_FORM', { embeddable }))
  .on('MODAL_SHOW_CREATE_PLAYLIST_FORM', (folder, playables) => showModal('CREATE_PLAYLIST_FORM', {
    folder: folder ?? null,
    playables: playables ? arrayify(playables) : [],
  }))
  .on('MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', folder => showModal('CREATE_SMART_PLAYLIST_FORM', {
    folder: folder ?? null,
  }))
  .on('MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM', () => showModal('CREATE_PLAYLIST_FOLDER_FORM'))
  .on('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist => showModal(
    playlist.is_smart ? 'EDIT_SMART_PLAYLIST_FORM' : 'EDIT_PLAYLIST_FORM',
    { playlist },
  ))
  .on('MODAL_SHOW_EDIT_ALBUM_FORM', album => showModal('EDIT_ALBUM_FORM', { album }))
  .on('MODAL_SHOW_EDIT_ARTIST_FORM', artist => showModal('EDIT_ARTIST_FORM', { artist }))
  .on('MODAL_SHOW_EDIT_USER_FORM', user => showModal('EDIT_USER_FORM', { user }))
  .on('MODAL_SHOW_EDIT_SONG_FORM', (songs, initialTab: EditSongFormTabName = 'details') => showModal('EDIT_SONG_FORM', {
    initialTab,
    songs: arrayify(songs),
  }))
  .on('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder => showModal('EDIT_PLAYLIST_FOLDER_FORM', { folder }))
  .on('MODAL_SHOW_PLAYLIST_COLLABORATION', playlist => showModal('PLAYLIST_COLLABORATION', { playlist }))
  .on('MODAL_SHOW_ADD_PODCAST_FORM', () => showModal('ADD_PODCAST_FORM'))
  .on('MODAL_SHOW_ADD_RADIO_STATION_FORM', () => showModal('ADD_RADIO_STATION_FORM'))
  .on('MODAL_SHOW_EDIT_RADIO_STATION_FORM', station => showModal('EDIT_RADIO_STATION_FORM', { station }))
  .on('MODAL_SHOW_EQUALIZER', () => showModal('EQUALIZER'))
</script>

<style lang="postcss" scoped>
dialog {
  :deep(> *) {
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
