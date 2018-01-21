import Component from '@/components/main-wrapper/main-content/album.vue'
import SongList from '@/components/shared/song-list.vue'
import SongListControls from '@/components/shared/song-list-controls.vue'
import { event } from '@/utils'
import { download, albumInfo as albumInfoService } from '@/services'
import factory from '@/tests/factory'

describe('components/main-wrapper/main-content/album', () => {
  it('renders upon receiving event', () => {
    const wrapper = shallow(Component)
    // stub the $nextTick so that the sort() method is not called
    // on non-existing $refs.songList
    const nextTickStub = sinon.stub(wrapper.vm, '$nextTick')
    const album = factory('album')
    event.emit('main-content-view:load', 'album', album)
    Vue.nextTick(() => {
      const html = wrapper.html()
      html.should.contain(album.name)
      html.should.contain(album.artist.name)
      wrapper.hasAll(SongList, SongListControls).should.be.true

      nextTickStub.restore()
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
    wrapper.click('a.info')
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
    wrapper.click('a.download')
    downloadStub.calledWith(album).should.be.true
    downloadStub.restore()
  })
})
