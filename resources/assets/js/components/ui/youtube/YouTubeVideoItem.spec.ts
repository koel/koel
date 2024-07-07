import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { youTubeService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import YouTubeVideoItem from './YouTubeVideoItem.vue'

let video: YouTubeVideo

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('plays', async () => {
      const mock = this.mock(youTubeService, 'play')
      this.renderComponent()

      await this.user.click(screen.getByRole('button'))

      expect(mock).toHaveBeenCalledWith(video)
    })
  }

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
}
