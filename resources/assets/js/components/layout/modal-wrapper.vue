<template>
  <div class="modal-wrapper" :class="{ overlay: showingModalName }">
    <create-smart-playlist-form v-if="showingModalName === 'create-smart-playlist-form'" @close="close"/>
    <edit-smart-playlist-form
      v-if="showingModalName === 'edit-smart-playlist-form'"
      @close="close"
      :playlist="boundData.playlist"
    />
    <add-user-form v-if="showingModalName === 'add-user-form'" @close="close"/>
    <edit-user-form v-if="showingModalName === 'edit-user-form'" :user="boundData.user" @close="close"/>
    <edit-song-form
      :songs="boundData.songs"
      :initialTab="boundData.initialTab"
      @close="close"
      v-if="showingModalName === 'edit-song-form'"
    />
    <about-dialog v-if="showingModalName === 'about-dialog'" @close="close"/>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import { eventBus } from '@/utils'

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

export default Vue.extend({
  components: {
    CreateSmartPlaylistForm: () => import('@/components/playlist/smart-playlist/create-form.vue'),
    EditSmartPlaylistForm: () => import('@/components/playlist/smart-playlist/edit-form.vue'),
    AddUserForm: () => import('@/components/user/add-form.vue'),
    EditUserForm: () => import('@/components/user/edit-form.vue'),
    EditSongForm: () => import('@/components/song/edit-form.vue'),
    AboutDialog: () => import('@/components/meta/about-dialog.vue')
  },

  data: () => ({
    showingModalName: null as ModalName | null,
    boundData: {} as ModalWrapperBoundData
  }),

  methods: {
    close (): void {
      this.showingModalName = null
      this.boundData = {}
    }
  },

  created (): void {
    eventBus.on({
      'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM': (): void => {
        this.showingModalName = 'create-smart-playlist-form'
      },

      'MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM': (playlist: Playlist): void => {
        this.boundData.playlist = playlist
        this.showingModalName = 'edit-smart-playlist-form'
      },

      'MODAL_SHOW_ADD_USER_FORM': (): void => {
        this.showingModalName = 'add-user-form'
      },

      'MODAL_SHOW_EDIT_USER_FORM': (user: User): void => {
        this.boundData.user = user
        this.showingModalName = 'edit-user-form'
      },

      'MODAL_SHOW_EDIT_SONG_FORM': (songs: Song[], initialTab: string = 'details'): void => {
        this.boundData.songs = songs
        this.boundData.initialTab = initialTab
        this.showingModalName = 'edit-song-form'
      },

      'MODAL_SHOW_ABOUT_DIALOG': (): void => {
        this.showingModalName = 'about-dialog'
      }
    })
  }
})
</script>
