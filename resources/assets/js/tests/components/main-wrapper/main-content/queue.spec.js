import Component from '@/components/main-wrapper/main-content/queue.vue'
import SongList from '@/components/shared/song-list.vue'
import factory from '@/tests/factory'
import { queueStore, songStore } from '@/stores'
import { playback } from '@/services'

describe('components/main-wrapper/main-content/queue', () => {
  it('renders properly', () => {
    const wrapper = shallow(Component, { data: {
      state: { songs: factory('song', 10) }
    }})
    wrapper.find('h1.heading').text().should.contain('Current Queue')
    wrapper.has(SongList).should.be.true
  })

  it('prompts to shuffle all songs if there are songs and current queue is empty', () => {
    songStore.state.songs = factory('song', 10)
    shallow(Component, { data: {
      state: { songs: [] }
    }}).find('a.start').text().should.contain('shuffling all songs')
  })

  it("doesn't prompt to shuffle all songs if there is no song", () => {
    songStore.state.songs = []
    shallow(Component, { data: {
      state: {
        songs: []
      }
    }}).has('a.start').should.be.false
  })

  it('shuffles all songs in the queue if any', () => {
    const stub = sinon.stub(playback, 'queueAndPlay')
    const songs = factory('song', 10)
    mount(Component, { data: {
      state: { songs }
    }}).click('button.btn-shuffle-all')
    stub.calledWith(songs).should.be.true
    stub.restore()
  })

  it('shuffles all available songs if there are no songs queued', () => {
    const stub = sinon.stub(playback, 'queueAndPlay')
    songStore.state.songs = factory('song', 10)
    mount(Component, { data: {
      state: {
        songs: []
      }
    }}).click('button.btn-shuffle-all')
    stub.calledWith(songStore.all).should.be.true
    stub.restore()
  })

  it('clears the queue', () => {
    const stub = sinon.stub(queueStore, 'clear')
    const wrapper = mount(Component)
    wrapper.setData({
      state: { songs: factory('song', 10) }
    })
    wrapper.click('button.btn-clear-queue')
    stub.called.should.be.true
    stub.restore()
  })
})
