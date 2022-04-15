import Component from '@/components/playlist/name-editor.vue'
import factory from '@/__tests__/factory'
import { playlistStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'

describe('components/playlist/name-editor', () => {
  let playlist: Playlist
  beforeEach(() => {
    playlist = factory<Playlist>('playlist', {
      id: 99,
      name: 'Foo'
    })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('updates a playlist', () => {
    const updateStub = mock(playlistStore, 'update')
    const wrapper = shallow(Component, {
      propsData: { playlist }
    })
    wrapper.find('[type=text]').setValue('Bar').input().blur()
    expect(updateStub).toHaveBeenCalledWith(expect.objectContaining({ id: 99, name: 'Bar' }))
  })
})
