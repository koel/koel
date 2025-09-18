import { describe, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { eventBus } from '@/utils/eventBus'
import type { Events } from '@/config/events'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ModalWrapper.vue'

describe('modalWrapper.vue', () => {
  const h = createHarness()

  it.each<[
    string,
    keyof Events,
      User | MaybeArray<Streamable> | Playlist | PlaylistFolder | Artist | Album | undefined,
  ]>([
    ['about-koel', 'MODAL_SHOW_ABOUT_KOEL', undefined],
    ['add-podcast-form', 'MODAL_SHOW_ADD_PODCAST_FORM', undefined],
    ['add-radio-station-form', 'MODAL_SHOW_ADD_RADIO_STATION_FORM', undefined],
    ['add-user-form', 'MODAL_SHOW_ADD_USER_FORM', undefined],
    ['create-embed-form', 'MODAL_SHOW_CREATE_EMBED_FORM', h.factory('song')],
    ['create-playlist-folder-form', 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM', undefined],
    ['create-playlist-form', 'MODAL_SHOW_CREATE_PLAYLIST_FORM', h.factory('playlist-folder')],
    ['create-smart-playlist-form', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM', h.factory('playlist-folder')],
    ['edit-album-form', 'MODAL_SHOW_EDIT_ALBUM_FORM', h.factory('album')],
    ['edit-artist-form', 'MODAL_SHOW_EDIT_ARTIST_FORM', h.factory('artist')],
    ['edit-playlist-folder-form', 'MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', h.factory('playlist-folder')],
    ['edit-playlist-form', 'MODAL_SHOW_EDIT_PLAYLIST_FORM', h.factory('playlist')],
    ['edit-radio-station-form', 'MODAL_SHOW_EDIT_RADIO_STATION_FORM', h.factory('radio-station')],
    ['edit-smart-playlist-form', 'MODAL_SHOW_EDIT_PLAYLIST_FORM', h.factory('playlist', { is_smart: true })],
    ['edit-song-form', 'MODAL_SHOW_EDIT_SONG_FORM', [h.factory('song')]],
    ['edit-user-form', 'MODAL_SHOW_EDIT_USER_FORM', h.factory('user')],
    ['equalizer', 'MODAL_SHOW_EQUALIZER', undefined],
    ['invite-user-form', 'MODAL_SHOW_INVITE_USER_FORM', undefined],
    ['koel-plus', 'MODAL_SHOW_KOEL_PLUS', undefined],
    ['playlist-collaboration', 'MODAL_SHOW_PLAYLIST_COLLABORATION', h.factory('playlist')],
  ])('shows %s modal', async (modalName, eventName, eventParams?: any) => {
    h.render(Component, {
      global: {
        stubs: {
          AboutKoelModal: h.stub('about-koel'),
          AddPodcastForm: h.stub('add-podcast-form'),
          AddRadioStationForm: h.stub('add-radio-station-form'),
          AddUserForm: h.stub('add-user-form'),
          CreateEmbedForm: h.stub('create-embed-form'),
          CreatePlaylistFolderForm: h.stub('create-playlist-folder-form'),
          CreatePlaylistForm: h.stub('create-playlist-form'),
          CreateSmartPlaylistForm: h.stub('create-smart-playlist-form'),
          EditAlbumForm: h.stub('edit-album-form'),
          EditArtistForm: h.stub('edit-artist-form'),
          EditPlaylistFolderForm: h.stub('edit-playlist-folder-form'),
          EditPlaylistForm: h.stub('edit-playlist-form'),
          EditRadioStationForm: h.stub('edit-radio-station-form'),
          EditSmartPlaylistForm: h.stub('edit-smart-playlist-form'),
          EditSongForm: h.stub('edit-song-form'),
          EditUserForm: h.stub('edit-user-form'),
          Equalizer: h.stub('equalizer'),
          InviteUserForm: h.stub('invite-user-form'),
          KoelPlus: h.stub('koel-plus'),
          PlaylistCollaborationModal: h.stub('playlist-collaboration'),
        },
      },
    })

    eventBus.emit(eventName, eventParams)

    await waitFor(() => screen.getByTestId(modalName))
  })
})
