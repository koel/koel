import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import Component from './AlbumTrackList.vue'

describe('albumTrackList.vue', () => {
  const h = createHarness()

  it('displays the tracks', async () => {
    const album = h.factory('album')
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(h.factory('song', 5))

    h.render(Component, {
      props: {
        album,
        tracks: h.factory('album-track', 3),
      },
    })

    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(screen.queryAllByTestId('album-track-item')).toHaveLength(3)
  })
})
