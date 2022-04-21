import { mock } from '@/__tests__/__helpers__'
import { preferenceStore as preferences } from '@/stores'
import { shallow } from '@/__tests__/adapter'
import Component from '@/components/profile-preferences/PreferencesForm.vue'
import factory from '@/__tests__/factory'

describe('profile-preferences/preferences', () => {
  beforeEach(() => preferences.init(factory('user')))

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it.each([['notify'], ['confirm_closing'], ['show_album_art_overlay']])('updates preference "%s"', key => {
    const m = mock(preferences, 'save')
    shallow(Component).change(`input[name=${key}]`)
    expect(m).toHaveBeenCalled()
  })
})
