import { it } from 'vitest'
import { waitFor } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { EventName } from '@/config'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ModalWrapper from './ModalWrapper.vue'

new class extends UnitTestCase {
  protected test () {
    it.each<[string, EventName, User | Song[] | Playlist | PlaylistFolder | undefined]>([
      ['add-user-form', 'MODAL_SHOW_ADD_USER_FORM', undefined],
      ['edit-user-form', 'MODAL_SHOW_EDIT_USER_FORM', factory<User>('user')],
      ['edit-song-form', 'MODAL_SHOW_EDIT_SONG_FORM', [factory<Song>('song')]],
      ['create-playlist-form', 'MODAL_SHOW_CREATE_PLAYLIST_FORM', undefined],
      ['create-playlist-folder-form', 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM', undefined],
      ['edit-playlist-folder-form', 'MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', factory<PlaylistFolder>('playlist-folder')],
      ['create-smart-playlist-form', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', undefined],
      ['edit-playlist-form', 'MODAL_SHOW_EDIT_PLAYLIST_FORM', factory<Playlist>('playlist')],
      ['edit-smart-playlist-form', 'MODAL_SHOW_EDIT_PLAYLIST_FORM', factory<Playlist>('playlist', { is_smart: true })],
      ['about-koel', 'MODAL_SHOW_ABOUT_KOEL', undefined]
    ])('shows %s modal', async (modalName: string, eventName: EventName, eventParams?: any) => {
      const { getByTestId } = this.render(ModalWrapper, {
        global: {
          stubs: {
            AddUserForm: this.stub('add-user-form'),
            EditUserForm: this.stub('edit-user-form'),
            EditSongForm: this.stub('edit-song-form'),
            CreatePlaylistForm: this.stub('create-playlist-form'),
            CreatePlaylistFolderForm: this.stub('create-playlist-folder-form'),
            EditPlaylistFolderForm: this.stub('edit-playlist-folder-form'),
            CreateSmartPlaylistForm: this.stub('create-smart-playlist-form'),
            EditPlaylistForm: this.stub('edit-playlist-form'),
            EditSmartPlaylistForm: this.stub('edit-smart-playlist-form'),
            AboutKoel: this.stub('about-koel')
          }
        }
      })

      eventBus.emit(eventName, eventParams)

      await waitFor(() => getByTestId(modalName))
    })
  }
}
