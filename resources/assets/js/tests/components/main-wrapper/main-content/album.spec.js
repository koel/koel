import Component from '@/components/main-wrapper/main-content/album.vue'
import SongList from '@/components/shared/song-list.vue'
import SongListControls from '@/components/shared/song-list-controls.vue'
import { event } from '@/utils'
import factory from '@/tests/factory'
import Vue from 'vue'

describe('components/main-wrapper/main-content/album', () => {
  it('renders upon receiving event', () => {
    const wrapper = shallow(Component)
    const album = factory('album')
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
    const wrapper = shallow(Component)
    wrapper.setData({
      album: factory('album'),
      sharedState: { useLastfm: true }
    })
    const showInfoStub = sinon.stub()
    wrapper.showInfo = showInfoStub
    wrapper.find('a.info').trigger('click')
    showInfoStub.should.have.been.called
  })

  it('allows downloading', () => {
    const wrapper = shallow(Component)
    wrapper.setData({
      album: factory('album'),
      sharedState: { allowDownload: true }
    })
    const downloadStub = sinon.stub()
    wrapper.download = downloadStub
    wrapper.find('a.download').trigger('click')
    downloadStub.should.have.been.called
  })
})
