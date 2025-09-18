import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { youTubeService } from './youTubeService'

describe('youTubeService', () => {
  const h = createHarness()

  it('plays a video', () => {
    const video = h.factory('you-tube-video', {
      id: {
        videoId: 'foo',
      },
      snippet: {
        title: 'Bar',
      },
    })

    const emitMock = h.mock(eventBus, 'emit')

    youTubeService.play(video)

    expect(emitMock).toHaveBeenCalledWith('PLAY_YOUTUBE_VIDEO', { id: 'foo', title: 'Bar' })
  })
})
