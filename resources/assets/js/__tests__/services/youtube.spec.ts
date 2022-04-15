import { youtube } from '@/services'
import { eventBus } from '@/utils'
import router from '@/router'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'

describe('services/youtube', () => {
  afterEach(() => {
    jest.resetModules()
    jest.restoreAllMocks()
    jest.clearAllMocks()
  })

  it('plays a video', () => {
    const video = factory<YouTubeVideo>('video', {
      id: {
        videoId: 'foo'
      },
      snippet: {
        title: 'Bar'
      }
    })
    const emitMock = mock(eventBus, 'emit')
    const goMock = mock(router, 'go')

    youtube.play(video)
    expect(emitMock).toHaveBeenCalledWith('PLAY_YOUTUBE_VIDEO', { id: 'foo', title: 'Bar' })
    expect(goMock).toHaveBeenCalledWith('youtube')
  })
})
