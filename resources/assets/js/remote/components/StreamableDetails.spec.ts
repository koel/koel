import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import StreamableDetails from './StreamableDetails.vue'

new class extends UnitTestCase {
  private renderComponent (streamable: Streamable) {
    return this.render(StreamableDetails, {
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

  protected test () {
    it('renders a song', () => {
      const { html } = this.renderComponent(factory('song', {
        title: 'Afraid to Shoot Strangers',
        album_name: 'Fear of the Dark',
        artist_name: 'Iron Maiden',
        album_cover: 'https://cover.site/fotd.jpg',
      }))

      expect(html()).toMatchSnapshot()
    })

    it('renders an episode', () => {
      const { html } = this.renderComponent(factory('episode', {
        title: 'Brahms Piano Concerto No. 1',
        podcast_title: 'The Sticky Notes podcast',
        podcast_author: 'Some random dudes',
        episode_image: 'https://cover.site/pod.jpg',
      }))

      expect(html()).toMatchSnapshot()
    })
  }
}
