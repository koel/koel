import Component from '@/components/ui/youtube-video-list.vue'
import { youtube as youtubeService } from '@/services'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'
import { mount } from '@/__tests__/adapter'

describe('components/ui/youtube', () => {
  let song: Song
  beforeEach(() => {
    song = factory<Song>('song', {
      youtube: {
        items: factory<YouTubeVideo>('video', 5),
        nextPageToken: 'f00'
      }
    })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('displays a list of videos', async () => {
    const wrapper = mount(Component, {
      propsData: { song }
    })

    await wrapper.vm.$nextTick()
    expect(wrapper.findAll('a.video')).toHaveLength(5)
  })

  it('loads more videos on demand', async () => {
    const wrapper = mount(Component, {
      propsData: { song }
    })

    const searchStub = mock(youtubeService, 'searchVideosRelatedToSong').mockReturnValue(Promise.resolve({
      nextPageToken: 'bar',
      items: factory<YouTubeVideo>('video', 5)
    }))

    await wrapper.vm.$nextTick()
    wrapper.click('button.more')
    expect(searchStub).toHaveBeenCalledWith(song, 'f00')
  })
})
