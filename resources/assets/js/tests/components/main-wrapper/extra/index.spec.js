import ExtraSidebar from '@/components/main-wrapper/extra/index.vue'
import ArtistInfo from '@/components/main-wrapper/extra/artist-info.vue'
import AlbumInfo from '@/components/main-wrapper/extra/album-info.vue'
import Lyrics from '@/components/main-wrapper/extra/lyrics.vue'
import YouTube from '@/components/main-wrapper/extra/youtube.vue'
import factory from '@/tests/factory'
import { event } from '@/utils'

describe('components/main-wrapper/extra/index', () => {
  it('shows by default', () => {
    const wrapper = shallow(ExtraSidebar)
    wrapper.findAll('#extra.showing').should.have.lengthOf(1)
  })

  it('has a YouTube tab if using YouTube', () => {
    const wrapper = shallow(ExtraSidebar)
    wrapper.findAll('.header .youtube').should.have.lengthOf(0)
    wrapper.setData({
      sharedState: { useYouTube: true }
    })
    wrapper.findAll('.header .youtube').should.have.lengthOf(1)
    wrapper.contains(YouTube).should.be.true
  })

  it('switches pane properly', () => {
    const wrapper = shallow(ExtraSidebar)
    expect(wrapper.find('.header .active').is('.lyrics')).toBe(true)
    ;['.artist', '.album', '.lyrics'].forEach(selector => {
      wrapper.find(`.header ${selector}`).trigger('click')
      wrapper.find('.header .active').is(selector).should.be.true
    })
  })

  it('has proper child components', () => {
    const wrapper = shallow(ExtraSidebar)
    wrapper.setData({
      song: factory('song'),
      sharedState: { useYouTube: true }
    })
    ;[ArtistInfo, AlbumInfo, Lyrics, YouTube].forEach(component => {
      wrapper.contains(component).should.be.true
    })
  })

  it('fetch song info when a new song is played', () => {
    const spy = sinon.spy()
    const wrapper = shallow(ExtraSidebar)
    const song = factory('song')
    wrapper.vm.fetchSongInfo = spy
    event.emit('song:played', song)
    spy.calledWith(song).should.be.true
  })
})
