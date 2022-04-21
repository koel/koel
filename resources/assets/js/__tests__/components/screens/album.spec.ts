import Component from '@/components/screens/album.vue'
import SongList from '@/components/song/SongList.vue'
import { albumInfo as albumInfoService, download } from '@/services'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/screens/album', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', async () => {
    const album = factory<Album>('album')
    const wrapper = mount(Component, {
      propsData: { album }
    })

    await wrapper.vm.$nextTick()
    expect(wrapper.hasAll(SongList)).toBe(true)
  })

  it('loads info from Last.fm', async () => {
    const album = factory<Album>('album', {
      info: null,
      songs: factory<Song>('song', 2)
    })
    const wrapper = await shallow(Component, {
      propsData: { album },
      data: () => ({
        sharedState: { useLastfm: true }
      })
    })
    const m = mock(albumInfoService, 'fetch')
    wrapper.click('a.info')
    expect(m).toHaveBeenCalledWith(album)
  })

  it('allows downloading', () => {
    const album = factory<Album>('album', {
      songs: factory<Song>('song', 2)
    })
    const wrapper = shallow(Component, {
      propsData: { album },
      data: () => ({
        sharedState: { allowDownload: true }
      })
    })
    const m = mock(download, 'fromAlbum')
    wrapper.click('a.download')
    expect(m).toHaveBeenCalledWith(album)
  })
})
