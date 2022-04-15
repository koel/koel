import Component from '@/components/album/card.vue'
import Thumbnail from '@/components/ui/album-artist-thumbnail.vue'
import factory from '@/__tests__/factory'
import { playback, download } from '@/services'
import { sharedStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/album/card', () => {
  let album: Album

  beforeEach(() => {
    album = factory<Album>('album', {
      songs: factory<Song>('song', 10)
    })
    // @ts-ignore
    sharedStore.state = { allowDownload: true }
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', async () => {
    const wrapper = mount(Component, { propsData: { album } })

    await wrapper.vm.$nextTick()
    expect(wrapper.has(Thumbnail)).toBe(true)
    const html = wrapper.html()
    expect(html).toMatch(album.name)
    expect(html).toMatch('10 songs')
  })

  it('shuffles', () => {
    const wrapper = shallow(Component, { propsData: { album } })
    const m = mock(playback, 'playAllInAlbum')

    wrapper.click('.shuffle-album')
    expect(m).toHaveBeenCalledWith(album, true)
  })

  it('downloads', () => {
    const wrapper = shallow(Component, { propsData: { album } })
    const m = mock(download, 'fromAlbum')

    wrapper.click('.download-album')
    expect(m).toHaveBeenCalledWith(album)
  })
})
