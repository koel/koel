import YouTube from '@/components/main-wrapper/extra/youtube.vue'
import song from '@/tests/blobs/song'
import videos from '@/tests/blobs/youtube-videos'

describe('components/main-wrapper/extra/youtube', () => {
  let wrapper
  beforeEach(() => {
    wrapper = shallow(YouTube, {
      propsData: { song }
    })
    wrapper.setData({ videos })
  })

  it('displays a list of videos', () => {
    wrapper.findAll('a.video').should.have.lengthOf(2)
  })

  it('loads more videos on demand', () => {
    const spy = sinon.spy()
    wrapper.vm.loadMore = spy
    wrapper.find('button.more').trigger('click')
    spy.should.have.been.called
  })
})
