import Component from '@/components/shared/album-item.vue'
import factory from '@/tests/factory'
import { playback, download } from '@/services'
import { queueStore, sharedStore } from '@/stores'

describe('components/shared/album-item', () => {
  let album

  beforeEach(() => {
    album = factory('album', {
      songs: factory('song', 10)
    })
  })

  it('renders properly', () => {
    const wrapper = shallow(Component, { propsData: { album }})
    wrapper.has('span.cover').should.be.true
    const html = wrapper.html()
    html.should.contain(album.name)
    html.should.contain('10 songs')
  })

  it('plays if clicked', () => {
    const playStub = sinon.stub(playback, 'playAllInAlbum')
    shallow(Component, { propsData: { album }}).click('.control-play')
    playStub.calledWith(album, false).should.be.true
    playStub.restore()
  })

  it('queues if ctrl/meta clicked', () => {
    const queueStub = sinon.stub(queueStore, 'queue')
    const wrapper = shallow(Component, { propsData: { album }})
    wrapper.click('.control-play', { metaKey: true })
    queueStub.called.should.be.true
    wrapper.click('.control-play', { ctrlKey: true })
    queueStub.called.should.be.true
    queueStub.restore()
  })

  it('shuffles', () => {
    const playStub = sinon.stub(playback, 'playAllInAlbum')
    shallow(Component, { propsData: { album }}).click('.shuffle-album')
    playStub.calledWith(album, true).should.be.true
    playStub.restore()
  })

  it('downloads', () => {
    sharedStore.state = { allowDownload: true }
    const downloadStub = sinon.stub(download, 'fromAlbum')
    shallow(Component, { propsData: { album }}).click('.download-album')
    downloadStub.calledWith(album).should.be.true
    downloadStub.restore()
  })
})
