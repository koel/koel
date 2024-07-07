import { it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { Events } from '@/config'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ModalWrapper from './ModalWrapper.vue'

new class extends UnitTestCase {
  protected test () {
    it.each<[string, keyof Events, User | Playable[] | Playlist | PlaylistFolder | undefined]>([
      ['add-user-form', 'MODAL_SHOW_ADD_USER_FORM', undefined],
      ['invite-user-form', 'MODAL_SHOW_INVITE_USER_FORM', undefined],
      ['edit-user-form', 'MODAL_SHOW_EDIT_USER_FORM', factory('user')],
      ['edit-song-form', 'MODAL_SHOW_EDIT_SONG_FORM', [factory('song')]],
      ['create-playlist-form', 'MODAL_SHOW_CREATE_PLAYLIST_FORM', factory('playlist-folder')],
      ['create-playlist-folder-form', 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM', undefined],
      ['edit-playlist-folder-form', 'MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', factory('playlist-folder')],
      ['playlist-collaboration', 'MODAL_SHOW_PLAYLIST_COLLABORATION', factory('playlist')],
      ['create-smart-playlist-form', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', factory('playlist-folder')],
      ['edit-playlist-form', 'MODAL_SHOW_EDIT_PLAYLIST_FORM', factory('playlist')],
      ['edit-smart-playlist-form', 'MODAL_SHOW_EDIT_PLAYLIST_FORM', factory('playlist', { is_smart: true })],
      ['about-koel', 'MODAL_SHOW_ABOUT_KOEL', undefined],
      ['koel-plus', 'MODAL_SHOW_KOEL_PLUS', undefined],
      ['equalizer', 'MODAL_SHOW_EQUALIZER', undefined],
      ['add-podcast-form', 'MODAL_SHOW_ADD_PODCAST_FORM', undefined]
    ])('shows %s modal', async (modalName, eventName, eventParams?: any) => {
      this.render(ModalWrapper, {
        global: {
          stubs: {
            AboutKoelModal: this.stub('about-koel'),
            AddPodcastForm: this.stub('add-podcast-form'),
            AddUserForm: this.stub('add-user-form'),
            CreatePlaylistFolderForm: this.stub('create-playlist-folder-form'),
            CreatePlaylistForm: this.stub('create-playlist-form'),
            CreateSmartPlaylistForm: this.stub('create-smart-playlist-form'),
            EditPlaylistFolderForm: this.stub('edit-playlist-folder-form'),
            EditPlaylistForm: this.stub('edit-playlist-form'),
            EditSmartPlaylistForm: this.stub('edit-smart-playlist-form'),
            EditSongForm: this.stub('edit-song-form'),
            EditUserForm: this.stub('edit-user-form'),
            Equalizer: this.stub('equalizer'),
            InviteUserForm: this.stub('invite-user-form'),
            KoelPlus: this.stub('koel-plus'),
            PlaylistCollaborationModal: this.stub('playlist-collaboration')
          }
        }
      })

      eventBus.emit(eventName, eventParams)

      await waitFor(() => screen.getByTestId(modalName))
    })
  }
}
