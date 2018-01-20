import Component from '@/components/shared/artist-item.vue'
import factory from '@/tests/factory'
import { playback, download } from '@/services'
import { queueStore, sharedStore } from '@/stores'

describe('components/shared/artist-item', () => {
  let artist

  beforeEach(() => {
    artist = factory('artist', {
      id: 3, // make sure it's not "Various Artists"
      albums: factory('album', 4),
      songs: factory('song', 16)
    })
  })

  it('renders properly', () => {
    const wrapper = shallow(Component, { propsData: { artist }})
    wrapper.has('span.cover').should.be.true
    const html = wrapper.html()
    html.should.contain('4 albums')
    html.should.contain('16 songs')
    html.should.contain(artist.name)
  })

  it('plays if clicked', () => {
    const playStub = sinon.stub(playback, 'playAllByArtist')
    shallow(Component, { propsData: { artist }}).click('.control-play')
    playStub.calledWith(artist, false).should.be.true
    playStub.restore()
  })

  it('queues if ctrl/meta clicked', () => {
    const queueStub = sinon.stub(queueStore, 'queue')
    const wrapper = shallow(Component, { propsData: { artist }})
    wrapper.click('.control-play', { metaKey: true })
    queueStub.called.should.be.true
    wrapper.click('.control-play', { ctrlKey: true })
    queueStub.called.should.be.true
    queueStub.restore()
  })

  it('shuffles', () => {
    const playStub = sinon.stub(playback, 'playAllByArtist')
    shallow(Component, { propsData: { artist }}).click('.shuffle-artist')
    playStub.calledWith(artist, true).should.be.true
    playStub.restore()
  })

  it('downloads', () => {
    sharedStore.state = { allowDownload: true }
    const downloadStub = sinon.stub(download, 'fromArtist')
    shallow(Component, { propsData: { artist }}).click('.download-artist')
    downloadStub.calledWith(artist).should.be.true
    downloadStub.restore()
  })
})
