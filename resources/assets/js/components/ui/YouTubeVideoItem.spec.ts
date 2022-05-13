import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { youTubeService } from '@/services'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import YouTubeVideoItem from './YouTubeVideoItem.vue'

let video: YouTubeVideo

new class extends ComponentTestCase {
  private renderComponent () {
    video = {
      id: {
        videoId: 'cLgJQ8Zj3AA'
      },
      snippet: {
        title: 'Guess what it is',
        description: 'From the LA Opening Gala 2014: John Williams Celebration',
        thumbnails: {
          default: {
            url: 'https://i.ytimg.com/an_webp/cLgJQ8Zj3AA/mqdefault_6s.webp'
          }
        }
      }
    }
    return this.render(YouTubeVideoItem, {
      props: {
        video
      }
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('plays', async () => {
      const mock = this.mock(youTubeService, 'play')
      const { getByRole } = this.renderComponent()

      await fireEvent.click(getByRole('button'))

      expect(mock).toHaveBeenCalledWith(video)
    })
  }
}
