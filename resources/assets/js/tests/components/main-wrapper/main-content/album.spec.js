import Album from '@/components/main-wrapper/main-content/album.vue'
import SongList from '@/components/shared/song-list.vue'
import SongListControls from '@/components/shared/song-list-controls.vue'
import { event } from '@/utils'
import album from '@/tests/blobs/album'
import Vue from 'vue'

describe('components/main-wrapper/main-content/album', () => {
  it('renders upon receiving event', () => {
    const wrapper = shallow(Album)
    event.emit('main-content-view:load', 'album', album)
    Vue.nextTick(() => {
      const html = wrapper.html()
      html.should.contain(album.name)
      html.should.contain(album.artist.name)
      wrapper.contains(SongList).should.be.true
      wrapper.contains(SongListControls).should.be.true
    })
  })

  it('loads info from Last.fm', () => {
    const wrapper = shallow(Album)
    wrapper.setData({
      album,
      sharedState: { useLastfm: true }
    })
    const spy = sinon.spy()
    wrapper.showInfo = spy
    wrapper.find('a.info').trigger('click')
    spy.should.have.been.called
  })

  it('allows downloading', () => {
    const wrapper = shallow(Album)
    wrapper.setData({
      album,
      sharedState: { allowDownload: true }
    })
    const spy = sinon.spy()
    wrapper.download = spy
    wrapper.find('a.download').trigger('click')
    spy.should.have.been.called
  })
})
