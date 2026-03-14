import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService, DownloadLimitExceededError } from './downloadService'
import { playableStore } from '@/stores/playableStore'

describe('downloadService', () => {
  const h = createHarness()

  it('downloads a single playable without pre-flight check', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    const checkMock = h.mock(downloadService, 'checkDownloadable')

    await downloadService.fromPlayables([h.factory('song', { id: 'bar' })])

    expect(checkMock).not.toHaveBeenCalled()
    expect(triggerMock).toHaveBeenCalledWith('songs?songs[]=bar')
  })

  it('downloads multiple playables with pre-flight check', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)

    const songs = [h.factory('song', { id: 'foo' }), h.factory('song', { id: 'bar' })]
    await downloadService.fromPlayables(songs)

    expect(triggerMock).toHaveBeenCalled()
  })

  it('does not download multiple playables if check fails', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockRejectedValue(new DownloadLimitExceededError('Limit exceeded'))

    const songs = [h.factory('song', { id: 'foo' }), h.factory('song', { id: 'bar' })]

    await expect(downloadService.fromPlayables(songs)).rejects.toThrow(DownloadLimitExceededError)
    expect(triggerMock).not.toHaveBeenCalled()
  })

  it('downloads all by artist', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const artist = h.factory('artist')

    await downloadService.fromArtist(artist)

    expect(triggerMock).toHaveBeenCalledWith(`artist/${artist.id}`)
  })

  it('downloads all in album', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const album = h.factory('album')

    await downloadService.fromAlbum(album)

    expect(triggerMock).toHaveBeenCalledWith(`album/${album.id}`)
  })

  it('downloads a playlist', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    const playlist = h.factory('playlist')

    await downloadService.fromPlaylist(playlist)

    expect(triggerMock).toHaveBeenCalledWith(`playlist/${playlist.id}`)
  })

  it('downloads favorites if available', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockResolvedValue(undefined)
    playableStore.state.favorites = h.factory('song', 5)

    await downloadService.fromFavorites()

    expect(triggerMock).toHaveBeenCalledWith('favorites')
  })

  it('does not download favorites if empty', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    playableStore.state.favorites = []

    await downloadService.fromFavorites()

    expect(triggerMock).not.toHaveBeenCalled()
  })

  it('throws DownloadLimitExceededError if check fails', async () => {
    const triggerMock = h.mock(downloadService, 'trigger')
    h.mock(downloadService, 'checkDownloadable').mockRejectedValue(new DownloadLimitExceededError('Limit exceeded'))

    await expect(downloadService.fromAlbum(h.factory('album'))).rejects.toThrow(DownloadLimitExceededError)
    expect(triggerMock).not.toHaveBeenCalled()
  })
})
