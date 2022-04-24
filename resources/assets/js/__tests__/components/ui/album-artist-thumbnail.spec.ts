import Component from '@/components/ui/AlbumArtistThumbnail.vue'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services'
import { queueStore, commonStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { Wrapper, shallow } from '@/__tests__/adapter'

describe('components/ui/album-artist-thumbnail(album)', () => {
  let album: Album
  let wrapper: Wrapper

  beforeEach(() => {
    album = factory<Album>('album', {
      songs: factory<Song>('song', 10)
    })
    // @ts-ignore
    commonStore.state = { allowDownload: true }
    wrapper = shallow(Component, { propsData: { entity: album } })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('plays if clicked', () => {
    const m = mock(playbackService, 'playAllInAlbum')
    wrapper.click('.control-play')
    expect(m).toHaveBeenCalledWith(album, false)
  })

  it.each([['metaKey'], ['ctrlKey']])('queues if %s is clicked', key => {
    const m = mock(queueStore, 'queue')
    wrapper.click('.control-play', { [key]: true })
    expect(m).toHaveBeenCalled()
  })
})

describe('components/ui/album-artist-thumbnail(artist)', () => {
  let artist: Artist
  let wrapper: Wrapper

  beforeEach(() => {
    // @ts-ignore
    commonStore.state = { allowDownload: true }
    artist = factory<Artist>('artist', {
      id: 3, // make sure it's not "Various Artists"
      albums: factory<Album>('album', 4),
      songs: factory<Song>('song', 16)
    })
    wrapper = shallow(Component, { propsData: { entity: artist } })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('plays if clicked', () => {
    const m = mock(playbackService, 'playAllByArtist')
    wrapper.click('.control-play')
    expect(m).toHaveBeenCalledWith(artist, false)
  })

  it.each([['metaKey'], ['ctrlKey']])('queues if %s is clicked', key => {
    const m = mock(queueStore, 'queue')
    wrapper.click('.control-play', { [key]: true })
    expect(m).toHaveBeenCalled()
  })
})
