import ExtraSidebar from '@/components/main-wrapper/extra/index.vue'
import ArtistInfo from '@/components/main-wrapper/extra/artist-info.vue'
import AlbumInfo from '@/components/main-wrapper/extra/album-info.vue'
import Lyrics from '@/components/main-wrapper/extra/lyrics.vue'
import YouTube from '@/components/main-wrapper/extra/youtube.vue'
import song from '@/tests/blobs/song'
import { event } from '@/utils'

describe('components/main-wrapper/extra/index', () => {
  it('shows by default', () => {
    const wrapper = shallow(ExtraSidebar)
    expect(wrapper.findAll('#extra.showing')).toHaveLength(1)
  })

  it('has a YouTube tab if using YouTube', () => {
    const wrapper = shallow(ExtraSidebar)
    expect(wrapper.findAll('.header .youtube')).toHaveLength(0)
    wrapper.setData({
      sharedState: { useYouTube: true }
    })
    expect(wrapper.findAll('.header .youtube')).toHaveLength(1)
    expect(wrapper.contains(YouTube)).toBe(true)
  })

  it('switches pane properly', () => {
    const wrapper = shallow(ExtraSidebar)
    expect(wrapper.find('.header .active').is('.lyrics')).toBe(true)
    ;['.artist', '.album', '.lyrics'].forEach(selector => {
      wrapper.find(`.header ${selector}`).trigger('click')
      expect(wrapper.find('.header .active').is(selector)).toBe(true)
    })
  })

  it('has proper child components', () => {
    const wrapper = shallow(ExtraSidebar)
    wrapper.setData({
      song,
      sharedState: { useYouTube: true }
    })
    ;[ArtistInfo, AlbumInfo, Lyrics, YouTube].forEach(component => {
      expect(wrapper.contains(component)).toBe(true)
    })
  })

  it('fetch song info when a new song is played', () => {
    const spy = sinon.spy()
    const wrapper = shallow(ExtraSidebar)
    wrapper.vm.fetchSongInfo = spy
    event.emit('song:played', song)
    expect(spy.calledWith(song)).toBe(true)
  })
})
