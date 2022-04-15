import { download } from '@/services'
import { favoriteStore } from '@/stores'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'

describe('services/download', () => {
  afterEach(() => {
    jest.resetModules()
    jest.restoreAllMocks()
    jest.clearAllMocks()
  })

  it('downloads songs', () => {
    const triggerMock = mock(download, 'trigger')
    download.fromSongs([factory<Song>('song', { id: 'foo' }), factory<Song>('song', { id: 'bar' })])

    expect(triggerMock).toHaveBeenCalledWith('songs?songs[]=bar&songs[]=foo&')
  })

  it('downloads all by artist', () => {
    const triggerMock = mock(download, 'trigger')
    download.fromArtist(factory<Artist>('artist', { id: 42 }))

    expect(triggerMock).toHaveBeenCalledWith('artist/42')
  })

  it('downloads all in album', () => {
    const triggerMock = mock(download, 'trigger')
    download.fromAlbum(factory<Album>('album', { id: 42 }))

    expect(triggerMock).toHaveBeenCalledWith('album/42')
  })

  it.each<[Song[], boolean]>([[[], false], [factory<Song>('song', 5), true]])
  ('downloads playlist if available', (songs, triggered) => {
    const triggerMock = mock(download, 'trigger')
    download.fromPlaylist(factory<Playlist>('playlist', { id: 42, songs }))

    triggered
      ? expect(triggerMock).toHaveBeenCalledWith('playlist/42')
      : expect(triggerMock).not.toHaveBeenCalled()
  })

  it.each<[Song[], boolean]>([[[], false], [factory<Song>('song', 5), true]])
  ('downloads favorites if available', (songs, triggered) => {
    const triggerMock = mock(download, 'trigger')
    favoriteStore.all = songs
    download.fromFavorites()

    triggered
      ? expect(triggerMock).toHaveBeenCalledWith('favorites')
      : expect(triggerMock).not.toHaveBeenCalled()
  })
})
