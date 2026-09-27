import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService } from './downloadService'
import { zipDownloadService } from '@/services/zipDownloadService'
import { playableStore } from '@/stores/playableStore'

describe('downloadService', () => {
  const h = createHarness()

  it('downloads a single playable without zipping', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    const zipMock = h.mock(zipDownloadService, 'start')
    const song = h.factory('song').make()

    await downloadService.fromPlayables([song])

    expect(zipMock).not.toHaveBeenCalled()
    expect(triggerMock).toHaveBeenCalledWith(song)
  })

  it('zips multiple playables', async () => {
    const zipMock = h.mock(zipDownloadService, 'start')
    const songs = h.factory('song').make(2)

    await downloadService.fromPlayables(songs)

    expect(zipMock).toHaveBeenCalledWith(songs, 'koel-download', 'none')
  })

  it('zips an artist’s songs', async () => {
    const zipMock = h.mock(zipDownloadService, 'start')
    const artist = h.factory('artist').make()
    const songs = h.factory('song').make(3)
    h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)

    await downloadService.fromArtist(artist)

    expect(zipMock).toHaveBeenCalledWith(songs, artist.name, 'none')
  })

  it('zips an album’s songs numbered by track', async () => {
    const zipMock = h.mock(zipDownloadService, 'start')
    const album = h.factory('album').make()
    const songs = h.factory('song').make(3)
    h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)

    await downloadService.fromAlbum(album)

    expect(zipMock).toHaveBeenCalledWith(songs, album.name, 'track')
  })

  it('zips a playlist numbered by position', async () => {
    const zipMock = h.mock(zipDownloadService, 'start')
    const playlist = h.factory('playlist').make()
    const songs = h.factory('song').make(3)
    h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue(songs)

    await downloadService.fromPlaylist(playlist)

    expect(zipMock).toHaveBeenCalledWith(songs, playlist.name, 'position')
  })

  it('zips favorites if there are any', async () => {
    const zipMock = h.mock(zipDownloadService, 'start')
    playableStore.state.favorites = h.factory('song').make(5)

    await downloadService.fromFavorites()

    expect(zipMock).toHaveBeenCalledWith(playableStore.state.favorites, 'Favorites', 'none')
  })

  it('does not download favorites if there are none', async () => {
    const zipMock = h.mock(zipDownloadService, 'start')
    playableStore.state.favorites = []

    await downloadService.fromFavorites()

    expect(zipMock).not.toHaveBeenCalled()
  })
})
