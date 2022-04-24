import Component from '@/components/album/track-list-item.vue'
import { commonStore, songStore, queueStore } from '@/stores'
import { playbackService, localStorageService } from '@/services'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'

describe('componnents/song/track-list-item', () => {
  let song: Song
  const track = {
    title: 'Foo and bar',
    fmtLength: '00:42'
  }
  const album = factory('album', { id: 42 })
  window.BASE_URL = 'http://koel.local/'

  beforeEach(() => {
    commonStore.state.useiTunes = true
    song = factory('song')
    mock(localStorageService, 'get', 'abcdef')
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders', () => {
    const wrapper = shallow(Component, {
      propsData: {
        track,
        album,
        index: 1
      }
    })
    expect(wrapper).toMatchSnapshot()
  })

  it('plays', () => {
    mock(songStore, 'guess', song)
    const containsStub = mock(queueStore, 'contains', false)
    const queueStub = mock(queueStore, 'queueAfterCurrent')
    const playStub = mock(playbackService, 'play')

    shallow(Component, {
      propsData: {
        track,
        album,
        index: 1
      }
    }).click('li')

    expect(containsStub).toHaveBeenCalledWith(song)
    expect(queueStub).toHaveBeenCalledWith(song)
    expect(playStub).toHaveBeenCalledWith(song)
  })
})
