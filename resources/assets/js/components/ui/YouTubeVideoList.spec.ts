import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { youTubeService } from '@/services'
import { screen, waitFor } from '@testing-library/vue'
import Btn from '@/components/ui/Btn.vue'
import YouTubeVideo from '@/components/ui/YouTubeVideoItem.vue'
import YouTubeVideoList from './YouTubeVideoList.vue'

new class extends UnitTestCase {
  protected test () {
    it('functions', async () => {
      const song = factory<Song>('song')

      const searchMock = this.mock(youTubeService, 'searchVideosBySong').mockResolvedValueOnce({
        nextPageToken: 'foo',
        items: factory<YouTubeVideo>('video', 5)
      }).mockResolvedValueOnce({
        nextPageToken: 'bar',
        items: factory<YouTubeVideo>('video', 3)
      })

      this.render(YouTubeVideoList, {
        props: {
          song
        },
        global: {
          stubs: {
            Btn,
            YouTubeVideo
          }
        }
      })

      await waitFor(() => {
        expect(searchMock).toHaveBeenNthCalledWith(1, song, '')
        expect(screen.getAllByRole('listitem')).toHaveLength(5)
      })

      await this.user.click(screen.getByRole('button', { name: 'Load More' }))

      await waitFor(() => {
        expect(searchMock).toHaveBeenNthCalledWith(2, song, 'foo')
        expect(screen.getAllByRole('listitem')).toHaveLength(8)
      })
    })
  }
}
