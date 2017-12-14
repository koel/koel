import YouTube from '@/components/main-wrapper/extra/youtube.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/extra/youtube', () => {
  let wrapper
  beforeEach(() => {
    wrapper = shallow(YouTube, {
      propsData: {
        song: factory('song')
      }
    })
    wrapper.setData({
      videos: factory('video', 5)
    })
  })

  it('displays a list of videos', () => {
    wrapper.findAll('a.video').should.have.lengthOf(5)
  })

  it('loads more videos on demand', () => {
    const loadMoreStub = sinon.stub()
    wrapper.vm.loadMore = loadMoreStub
    wrapper.find('button.more').trigger('click')
    loadMoreStub.should.have.been.called
  })
})
