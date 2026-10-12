import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import EmbedWidgetTrackItem from './EmbedWidgetTrackItem.vue'

describe('playableEmbedItem.vue', async () => {
  const h = createHarness()

  const renderComponent = (playable: Playable) => {
    const rendered = h.render(EmbedWidgetTrackItem, {
      props: {
        item: {
          playable,
          selected: false,
        } satisfies PlayableRow,
      },
      global: {
        stubs: {
          PlayableThumbnail: h.stub('playable-thumbnail'),
        },
      },
    })

    return {
      ...rendered,
      playable,
    }
  }

  it('shows the track number, artist, and album of a song', () => {
    const song = h.factory('song').make({ track: 9 })
    renderComponent(song)

    screen.getByText('9')
    screen.getByText(`${song.artist_name} - ${song.album_name}`)
  })

  it('shows the author and podcast of an episode', () => {
    const episode = h.factory('episode').make()
    renderComponent(episode)

    screen.getByText(`${episode.podcast_author} - ${episode.podcast_title}`)
  })

  it('emits the play event on double-click', async () => {
    const { playable, emitted } = renderComponent(h.factory('song').make())
    await h.user.dblClick(screen.getByTestId('playable-embed-item'))
    expect(emitted().play[0]).toEqual([playable])
  })
})
