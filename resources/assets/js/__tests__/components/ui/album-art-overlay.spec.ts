import factory from '@/__tests__/factory'
import Component from '@/components/ui/AlbumArtOverlay.vue'
import { albumStore } from '@/stores/album'
import { shallow } from '@/__tests__/adapter'
import { mock } from '@/__tests__/__helpers__'
import { preferenceStore } from '@/stores'

describe('components/ui/AlbumArtOverlay', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('requests album thumbnail', async () => {
    preferenceStore.state.showAlbumArtOverlay = true
    const getCoverThumbnailMock = mock(albumStore, 'getThumbnail').mockResolvedValue('http://localhost/foo_thumb.jpg')

    const song = factory<Song>('song')
    const wrapper = shallow(Component)
    wrapper.setProps({ song })
    expect(getCoverThumbnailMock).toHaveBeenCalledWith(song.album)
    expect(wrapper).toMatchSnapshot()
  })
})
