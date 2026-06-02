import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { useRate } from './useRate'

describe('useRate', () => {
  const h = createHarness()

  it('routes a song to playableStore.rate', async () => {
    const song = h.factory('song').make()
    const spy = h.mock(playableStore, 'rate').mockResolvedValue(undefined)

    const { rate } = useRate()
    await rate(song, 4)

    expect(spy).toHaveBeenCalledWith(song, 4)
  })

  it('routes an album to albumStore.rate', async () => {
    const album = h.factory('album').make()
    const spy = h.mock(albumStore, 'rate').mockResolvedValue(undefined)

    const { rate } = useRate()
    await rate(album, 3)

    expect(spy).toHaveBeenCalledWith(album, 3)
  })

  it('routes an artist to artistStore.rate', async () => {
    const artist = h.factory('artist').make()
    const spy = h.mock(artistStore, 'rate').mockResolvedValue(undefined)

    const { rate } = useRate()
    await rate(artist, 5)

    expect(spy).toHaveBeenCalledWith(artist, 5)
  })

  it('does not cross-dispatch between stores', async () => {
    const album = h.factory('album').make()
    const albumSpy = h.mock(albumStore, 'rate').mockResolvedValue(undefined)
    const artistSpy = h.mock(artistStore, 'rate').mockResolvedValue(undefined)
    const songSpy = h.mock(playableStore, 'rate').mockResolvedValue(undefined)

    const { rate } = useRate()
    await rate(album, 2)

    expect(albumSpy).toHaveBeenCalledOnce()
    expect(artistSpy).not.toHaveBeenCalled()
    expect(songSpy).not.toHaveBeenCalled()
  })
})
