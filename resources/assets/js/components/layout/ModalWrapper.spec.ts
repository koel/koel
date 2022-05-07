import factory from '@/__tests__/factory'
import { mockHelper, render, stub } from '@/__tests__/__helpers__'
import { httpService } from '@/services'
import { eventBus } from '@/utils'
import { beforeEach, it } from 'vitest'
import { cleanup } from '@testing-library/vue'
import { EventName } from '@/config'
import ModalWrapper from './ModalWrapper.vue'

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
})

it.each<[string, EventName, User | Song | never]>([
  ['add-user-form', 'MODAL_SHOW_ADD_USER_FORM'],
  ['edit-user-form', 'MODAL_SHOW_EDIT_USER_FORM', factory('user')],
  ['edit-song-form', 'MODAL_SHOW_EDIT_SONG_FORM', [factory('song')]],
  ['create-smart-playlist-form', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM'],
  ['edit-smart-playlist-form', 'MODAL_SHOW_EDIT_SMART_PLAYLIST_FORM', factory('playlist')],
  ['about-koel', 'MODAL_SHOW_ABOUT_KOEL']
])('shows %s modal', async (modalName: string, eventName: EventName, eventParams?: any) => {
  if (modalName === 'edit-song-form') {
    // mocking the songInfoService.fetch() request made during edit-form modal opening
    mockHelper.mock(httpService, 'request').mockReturnValue(Promise.resolve({ data: {} }))
  }

  const { findByTestId } = render(ModalWrapper, {
    global: {
      stubs: {
        CreateSmartPlaylistForm: stub('create-smart-playlist-form'),
        EditSmartPlaylistForm: stub('edit-smart-playlist-form'),
        AddUserForm: stub('add-user-form'),
        EditUserForm: stub('edit-user-form'),
        EditSongForm: stub('edit-song-form'),
        AboutKoel: stub('about-koel')
      }
    }
  })

  eventBus.emit(eventName, eventParams)

  findByTestId(modalName)
})
