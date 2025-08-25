import { describe, expect, it } from 'vitest'
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

  it('renders a song', () => {
    const { html } = renderComponent(h.factory('song', {
      title: 'Afraid to Shoot Strangers',
      album_name: 'Fear of the Dark',
      artist_name: 'Iron Maiden',
      album_cover: 'https://cover.site/fotd.jpg',
    }))

    expect(html()).toMatchSnapshot()
  })

  it('renders an episode', () => {
    const { html } = renderComponent(h.factory('episode', {
      title: 'Brahms Piano Concerto No. 1',
      podcast_title: 'The Sticky Notes podcast',
      podcast_author: 'Some random dudes',
      episode_image: 'https://cover.site/pod.jpg',
    }))

    expect(html()).toMatchSnapshot()
  })
})
