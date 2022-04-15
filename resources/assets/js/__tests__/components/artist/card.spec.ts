import Component from '@/components/artist/card.vue'
import Thumbnail from '@/components/ui/album-artist-thumbnail.vue'
import factory from '@/__tests__/factory'
import { playback, download } from '@/services'
import { sharedStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/artist/card', () => {
  let artist: Artist

  beforeEach(() => {
    // @ts-ignore
    sharedStore.state = { allowDownload: true }
    artist = factory<Artist>('artist', {
      id: 3, // make sure it's not "Various Artists"
      albums: factory<Album>('album', 4),
      songs: factory<Song>('song', 16)
    })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', async () => {
    const wrapper = mount(Component, { propsData: { artist } })

    await wrapper.vm.$nextTick()
    expect(wrapper.has(Thumbnail)).toBe(true)
    const html = wrapper.html()
    expect(html).toMatch('4 albums')
    expect(html).toMatch('16 songs')
    expect(html).toMatch(artist.name)
  })

  it('shuffles', () => {
    const wrapper = shallow(Component, { propsData: { artist } })
    const playStub = mock(playback, 'playAllByArtist')

    wrapper.click('.shuffle-artist')
    expect(playStub).toHaveBeenCalledWith(artist, true)
  })

  it('downloads', () => {
    const wrapper = shallow(Component, { propsData: { artist } })
    const downloadStub = mock(download, 'fromArtist')

    wrapper.click('.download-artist')
    expect(downloadStub).toHaveBeenCalledWith(artist)
  })
})
