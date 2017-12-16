import Component from '@/components/main-wrapper/main-content/queue.vue'
import SongList from '@/components/shared/song-list.vue'
import factory from '@/tests/factory'
import { queueStore, songStore } from '@/stores'
import { playback } from '@/services'

describe('components/main-wrapper/main-content/queue', () => {
  it('renders properly', () => {
    const wrapper = shallow(Component)
    wrapper.setData({
      state: { songs: factory('song', 10) }
    })
    wrapper.find('h1.heading').text().should.contain('Current Queue')
    wrapper.contains(SongList).should.be.true
  })

  it('prompts to shuffle all songs if there are songs and current queue is empty', () => {
    songStore.state.songs = factory('song', 10)
    const wrapper = shallow(Component)
    wrapper.setData({
      state: { songs: [] }
    })
    wrapper.find('a.start').text().should.contain('shuffling all songs')
  })

  it("doesn't prompt to shuffle all songs if there is no song", () => {
    songStore.state.songs = []
    const wrapper = shallow(Component)
    wrapper.setData({
      state: { songs: [] }
    })
    wrapper.findAll('a.start').should.have.lengthOf(0)
  })

  it('shuffles all songs in the queue if any', () => {
    const stub = sinon.stub(playback, 'queueAndPlay')
    const wrapper = mount(Component)
    const songs = factory('song', 10)
    wrapper.setData({
      state: { songs }
    })
    wrapper.find('button.btn-shuffle-all').trigger('click')
    stub.calledWith(songs).should.be.true
    stub.restore()
  })

  it('shuffles all available songs if there are no songs queued', () => {
    const stub = sinon.stub(playback, 'queueAndPlay')
    const wrapper = mount(Component)
    songStore.state.songs = factory('song', 10)
    wrapper.setData({
      state: { songs: [] }
    })
    wrapper.find('button.btn-shuffle-all').trigger('click')
    stub.calledWith(songStore.all).should.be.true
    stub.restore()
  })

  it('clears the queue', () => {
    const stub = sinon.stub(queueStore, 'clear')
    const wrapper = mount(Component)
    wrapper.setData({
      state: { songs: factory('song', 10) }
    })
    wrapper.find('button.btn-clear-queue').trigger('click')
    stub.called.should.be.true
    stub.restore()
  })
})
