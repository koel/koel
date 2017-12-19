import YouTube from '@/components/main-wrapper/extra/youtube.vue'
import { youtube as youtubeService } from '@/services'
import factory from '@/tests/factory'

describe('components/main-wrapper/extra/youtube', () => {
  let wrapper
  let song
  beforeEach(() => {
    song = factory('song')
    wrapper = shallow(YouTube, {
      propsData: { song },
      data: {
        videos: factory('video', 5)
      }
    })
  })

  it('displays a list of videos', () => {
    wrapper.findAll('a.video').should.have.lengthOf(5)
  })

  it('loads more videos on demand', () => {
    const stub = sinon.stub(youtubeService, 'searchVideosRelatedToSong')
    wrapper.find('button.more').trigger('click')
    stub.calledWith(song).should.be.true
    stub.restore()
  })
})
