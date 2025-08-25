import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { PlayablesKey } from '@/symbols'
import Component from './AlbumTrackListItem.vue'

describe('albumTrackListItem.vue', () => {
  const h = createHarness()

  const renderComponent = (matchedSong?: Song) => {
    const songsToMatchAgainst = h.factory('song', 10)
    const album = h.factory('album')

    const track = h.factory('album-track', {
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

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('plays', async () => {
    h.createAudioPlayer()

    const matchedSong = h.factory('song')
    const playMock = h.mock(playbackService, 'play')

    renderComponent(matchedSong)

    await h.user.click(screen.getByTitle('Click to play'))

    expect(playMock).toHaveBeenCalledWith(matchedSong)
  })
})
