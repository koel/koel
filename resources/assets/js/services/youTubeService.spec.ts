import { eventBus } from '@/utils'
import router from '@/router'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { youTubeService } from './youTubeService'

new class extends UnitTestCase {
  protected test () {
    it('plays a video', () => {
      const video = factory<YouTubeVideo>('video', {
        id: {
          videoId: 'foo'
        },
        snippet: {
          title: 'Bar'
        }
      })

      const emitMock = this.mock(eventBus, 'emit')
      const goMock = this.mock(router, 'go')

      youTubeService.play(video)

      expect(emitMock).toHaveBeenCalledWith('PLAY_YOUTUBE_VIDEO', { id: 'foo', title: 'Bar' })
      expect(goMock).toHaveBeenCalledWith('youtube')
    })
  }
}
