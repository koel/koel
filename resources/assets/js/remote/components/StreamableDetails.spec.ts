import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import StreamableDetails from './StreamableDetails.vue'

describe('streamableDetails.vue', () => {
  const h = createHarness()

  const renderComponent = (streamable: Streamable) => {
    return h.render(StreamableDetails, {
      props: {
        streamable,
      },
      global: {
        provide: {
          state: {
            streamable,
            volume: 7,
          },
        },
      },
    })
  }

  it('shows the details of a song', () => {
    const song = h.factory('song').make()
    renderComponent(song)

    expect(screen.getByRole('img').getAttribute('src')).toBe(song.album_cover)
    screen.getByText(song.artist_name)
    screen.getByText(song.album_name)
  })

  it('shows the details of an episode', () => {
    const episode = h.factory('episode').make()
    renderComponent(episode)

    expect(screen.getByRole('img').getAttribute('src')).toBe(episode.episode_image)
    screen.getByText(episode.podcast_author)
    screen.getByText(episode.podcast_title)
  })
})
