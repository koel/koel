import Component from '@/components/layout/main-wrapper/extra-panel.vue'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { songInfo } from '@/services'
import { mock } from '@/__tests__/__helpers__'
import { shallow, Wrapper } from '@/__tests__/adapter'

const shallowComponent = (data: object = {}): Wrapper => shallow(Component, {
  stubs: ['lyrics-pane', 'artist-info', 'album-info', 'you-tube-video-list'],
  data: () => data
})

describe('components/layout/extra-panel', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', () => {
    expect(shallowComponent()).toMatchSnapshot()
  })

  it('does not have a YouTube tab if not using YouTube', async () => {
    const wrapper = shallowComponent({
      sharedState: {
        useYouTube: false
      }
    })
    await wrapper.vm.$nextTick()
    expect(shallow(Component)).toMatchSnapshot()
  })

  it('has a YouTube tab if using YouTube', async () => {
    const wrapper = shallowComponent({
      sharedState: {
        useYouTube: true
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.has('#extraTabYouTube')).toBe(true)
  })

  it.each<[string]>([['#extraTabLyrics'], ['#extraTabAlbum'], ['#extraTabArtist']])
  ('switches to "%s" tab', selector => {
    expect(shallowComponent().click(selector).find('[aria-selected=true]').is(selector)).toBe(true)
  })

  it('fetch song info when a new song is played', () => {
    shallowComponent()
    const song = factory('song')
    const m = mock(songInfo, 'fetch', song)
    eventBus.emit('SONG_STARTED', song)
    expect(m).toHaveBeenCalledWith(song)
  })
})
