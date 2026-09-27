import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService, DownloadLimitExceededError } from './downloadService'
import { zipDownloadService } from '@/services/zipDownloadService'
import { playableStore } from '@/stores/playableStore'

describe('downloadService', () => {
  const h = createHarness()

  it('downloads a single playable without pre-flight check or zipping', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    const checkMock = h.mock(downloadService, 'checkDownloadable')
    const zipMock = h.mock(zipDownloadService, 'start')
    const song = h.factory('song').make()

    await downloadService.fromPlayables([song])

    expect(checkMock).not.toHaveBeenCalled()
    expect(zipMock).not.toHaveBeenCalled()
    expect(triggerMock).toHaveBeenCalledWith(song)
  })

  it('zips multiple playables after a pre-flight check', async () => {
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const zipMock = h.mock(zipDownloadService, 'start').mockResolvedValue(undefined)
    const songs = h.factory('song').make(2)

    await downloadService.fromPlayables(songs)

    expect(zipMock).toHaveBeenCalledWith(songs, 'Songs', 'none')
  })

  it('does not zip multiple playables if the check fails', async () => {
    h.mock(downloadService, 'checkDownloadable').mockRejectedValue(new DownloadLimitExceededError('Limit exceeded'))
    const zipMock = h.mock(zipDownloadService, 'start')

    await expect(downloadService.fromPlayables(h.factory('song').make(2))).rejects.toThrow(DownloadLimitExceededError)
    expect(zipMock).not.toHaveBeenCalled()
  })

  it('zips an artist’s songs', async () => {
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const zipMock = h.mock(zipDownloadService, 'start').mockResolvedValue(undefined)
    const artist = h.factory('artist').make()
    const songs = h.factory('song').make(3)
    h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)

    await downloadService.fromArtist(artist)

    expect(zipMock).toHaveBeenCalledWith(songs, artist.name, 'none')
  })

  it('zips an album’s songs numbered by track', async () => {
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const zipMock = h.mock(zipDownloadService, 'start').mockResolvedValue(undefined)
    const album = h.factory('album').make()
    const songs = h.factory('song').make(3)
    h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)

    await downloadService.fromAlbum(album)

    expect(zipMock).toHaveBeenCalledWith(songs, album.name, 'track')
  })

  it('zips a playlist numbered by position', async () => {
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const zipMock = h.mock(zipDownloadService, 'start').mockResolvedValue(undefined)
    const playlist = h.factory('playlist').make()
    const songs = h.factory('song').make(3)
    h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue(songs)

    await downloadService.fromPlaylist(playlist)

    expect(zipMock).toHaveBeenCalledWith(songs, playlist.name, 'position')
  })

  it('zips favorites if there are any', async () => {
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const zipMock = h.mock(zipDownloadService, 'start').mockResolvedValue(undefined)
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

  it('throws DownloadLimitExceededError if the check fails', async () => {
    h.mock(downloadService, 'checkDownloadable').mockRejectedValue(new DownloadLimitExceededError('Limit exceeded'))
    const zipMock = h.mock(zipDownloadService, 'start')

    await expect(downloadService.fromAlbum(h.factory('album').make())).rejects.toThrow(DownloadLimitExceededError)
    expect(zipMock).not.toHaveBeenCalled()
  })
})
