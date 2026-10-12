import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { PlayablesKey } from '@/config/symbols'
import Component from './AlbumTrackListItem.vue'

describe('albumTrackListItem.vue', () => {
  const h = createHarness()

  const renderComponent = (matchedSong?: Song) => {
    const songsToMatchAgainst = h.factory('song').make(10)
    const album = h.factory('album').make()

    const track = h.factory('album-track').make({
      title: 'Fahrstuhl to Heaven',
      length: 280,
    })

    const matchMock = h.mock(playableStore, 'matchSongsByTitle', matchedSong)

    const rendered = h.render(Component, {
      props: {
        album,
        track,
      },
      global: {
        provide: {
          [<symbol>PlayablesKey]: ref(songsToMatchAgainst),
        },
      },
    })

    expect(matchMock).toHaveBeenCalledWith('Fahrstuhl to Heaven', songsToMatchAgainst)

    return rendered
  }

  it('plays', async () => {
    h.createAudioPlayer()

    const matchedSong = h.factory('song').make()
    const playMock = h.mock(playbackService, 'play')

    renderComponent(matchedSong)

    await h.user.click(screen.getByText('Fahrstuhl to Heaven'))

    expect(playMock).toHaveBeenCalledWith(matchedSong)
  })

  it.each(['{Enter}', ' '])('plays when %s is pressed', async (key: string) => {
    h.createAudioPlayer()

    const matchedSong = h.factory('song').make()
    const playMock = h.mock(playbackService, 'play')

    renderComponent(matchedSong)

    await h.user.tab()
    await h.user.keyboard(key)

    expect(playMock).toHaveBeenCalledWith(matchedSong)
  })
})
