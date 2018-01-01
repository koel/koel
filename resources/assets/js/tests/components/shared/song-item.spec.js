import Component from '@/components/shared/song-item.vue'
import factory from '@/tests/factory'
import { playback } from '@/services'
import { queueStore } from '@/stores'

describe('components/shared/song-item', () => {
  let item, song, artist, album

  beforeEach(() => {
    artist = factory('artist')
    album = factory('album', {
      artist, 
      artist_id: artist.id
    })

    song = factory('song', {
      artist,
      album,
      artist_id: artist.id,
      album_id: album.id,
      fmtLength: '04:56'
    })

    item = { song, selected: false }
  })

  it('renders properly', () => {
    const html = shallow(Component, { propsData: { item }}).html()
    html.should.contain(song.track)
    html.should.contain(song.title)
    html.should.contain(artist.name)
    html.should.contain(album.name)
    html.should.contain('04:56')
  })

  it('queues and plays if unqueued', () => {
    const containsStub = sinon.stub(queueStore, 'contains').callsFake(() => false)
    const queueStub = sinon.stub(queueStore, 'queueAfterCurrent')
    const playStub = sinon.stub(playback, 'play')
    const wrapper = shallow(Component, { propsData: { item }})
    wrapper.find('tr').trigger('dblclick')
    containsStub.calledWith(song).should.be.true
    queueStub.calledWith(song).should.be.true
    playStub.calledWith(song).should.be.true

    containsStub.restore()
    queueStub.restore()
    playStub.restore()
  })

  it('just plays if queued', () => {
    const containsStub = sinon.stub(queueStore, 'contains').callsFake(() => true)
    const queueStub = sinon.stub(queueStore, 'queueAfterCurrent')
    const playStub = sinon.stub(playback, 'play')
    const wrapper = shallow(Component, { propsData: { item }})
    wrapper.find('tr').trigger('dblclick')
    containsStub.calledWith(song).should.be.true
    queueStub.calledWith(song).should.be.false
    playStub.calledWith(song).should.be.true

    containsStub.restore()
    queueStub.restore()
    playStub.restore()
  })

  it('handles playback correctly', () => {
    const playStub = sinon.stub(playback, 'play')
    const resumeStub = sinon.stub(playback, 'resume') 
    const pauseStub = sinon.stub(playback, 'pause')
    const playControl = shallow(Component, { propsData: { item }}).find('.play')

    playControl.trigger('click')
    playStub.called.should.be.true 

    song.playbackState = 'playing'
    playControl.trigger('click')
    pauseStub.called.should.be.true 

    song.playbackState = 'paused'
    playControl.trigger('click')
    resumeStub.called.should.be.true 

    playStub.restore()
    resumeStub.restore()
    pauseStub.restore()
  })
})
