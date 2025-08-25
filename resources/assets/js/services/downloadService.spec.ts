import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { downloadService } from './downloadService'
import { playableStore } from '@/stores/playableStore'

describe('downloadService', () => {
  const h = createHarness()

  it('downloads playables', () => {
    const mock = h.mock(downloadService, 'trigger')
    downloadService.fromPlayables([h.factory('song', { id: 'bar' })])

    expect(mock).toHaveBeenCalledWith('songs?songs[]=bar&')
  })

  it('downloads all by artist', () => {
    const mock = h.mock(downloadService, 'trigger')
    const artist = h.factory('artist')
    downloadService.fromArtist(artist)

    expect(mock).toHaveBeenCalledWith(`artist/${artist.id}`)
  })

  it('downloads all in album', () => {
    const mock = h.mock(downloadService, 'trigger')
    const album = h.factory('album')
    downloadService.fromAlbum(album)

    expect(mock).toHaveBeenCalledWith(`album/${album.id}`)
  })

  it('downloads a playlist', () => {
    const mock = h.mock(downloadService, 'trigger')
    const playlist = h.factory('playlist')

    downloadService.fromPlaylist(playlist)

    expect(mock).toHaveBeenCalledWith(`playlist/${playlist.id}`)
  })

  it.each<[Playable[], boolean]>([[[], false], [h.factory('song', 5), true]])(
    'downloads favorites if available',
    (songs, triggered) => {
      const mock = h.mock(downloadService, 'trigger')
      playableStore.state.favorites = songs

      downloadService.fromFavorites()

      triggered ? expect(mock).toHaveBeenCalledWith('favorites') : expect(mock).not.toHaveBeenCalled()
    },
  )
})
