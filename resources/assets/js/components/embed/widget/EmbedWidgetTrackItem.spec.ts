import { describe, expect, it } from 'vitest'
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

  it('renders a song', () => {
    const { html } = renderComponent(h.factory('song', {
      title: 'Bohemian Rhapsody',
      length: 280,
      artist_name: 'Queen',
      album_name: 'A Night at the Opera',
      track: 9,
    }))

    expect(html()).toMatchSnapshot()
  })

  it('renders a podcast episode', () => {
    const { html } = renderComponent(h.factory('episode', {
      title: 'How to tell people to shut up about Queen',
      length: 280,
      podcast_title: 'The Everyday Guide',
      podcast_author: 'The Everyday Guy',
    }))

    expect(html()).toMatchSnapshot()
  })

  it('emits the play event on double-click', async () => {
    const { playable, emitted } = renderComponent(h.factory('song'))
    await h.user.dblClick(screen.getByTestId('playable-embed-item'))
    expect(emitted().play[0]).toEqual([playable])
  })
})
