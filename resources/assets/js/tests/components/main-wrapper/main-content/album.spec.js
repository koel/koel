import Component from '@/components/main-wrapper/main-content/album.vue'
import SongList from '@/components/shared/song-list.vue'
import SongListControls from '@/components/shared/song-list-controls.vue'
import { event } from '@/utils'
import { download, albumInfo as albumInfoService } from '@/services'
import factory from '@/tests/factory'

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
    const album = factory('album', { info: null })
    const wrapper = shallow(Component, {
      data: {
        album,
        sharedState: { useLastfm: true }
      }
    })
    const stub = sinon.stub(albumInfoService, 'fetch')
    wrapper.find('a.info').trigger('click')
    stub.calledWith(album).should.be.true
    stub.restore()
  })

  it('allows downloading', () => {
    const album = factory('album')
    const wrapper = shallow(Component, {
      data: {
        album,
        sharedState: { allowDownload: true }
      }
    })
    const downloadStub = sinon.stub(download, 'fromAlbum')
    wrapper.find('a.download').trigger('click')
    downloadStub.calledWith(album).should.be.true
    downloadStub.restore()
  })
})
