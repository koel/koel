import Component from '@/components/shared/add-to-menu.vue'
import factory from '@/tests/factory'
import { playlistStore, queueStore, favoriteStore } from '@/stores'

describe('components/shared/add-to-menu', () => {
  const config = {
    queue: true,
    favorites: true,
    playlists: true,
    newPlaylist: true
  }

  let songs

  const initComponent = (customConfig = {}, func = shallow) => {
    songs = factory('song', 5)
    return func(Component, { propsData: {
      songs,
      config: _.assign(_.clone(config), customConfig),
      showing: true
    }})
  }

  it('renders', () => {
    playlistStore.all = factory('playlist', 10)
    const wrapper = initComponent()
    wrapper.html().should.contain('Add 5 songs to')
    wrapper.hasAll('li.after-current', 'li.bottom-queue', 'li.top-queue', 'li.favorites', 'form.form-new-playlist').should.be.true
    wrapper.findAll('li.playlist').should.have.lengthOf(10)
  })

  it('supports different configurations', () => {
    // add to queue
    let wrapper = initComponent({ queue: false })
    wrapper.hasNone('li.after-current', 'li.bottom-queue', 'li.top-queue').should.be.true

    // add to favorites
    wrapper = initComponent({ favorites: false })
    wrapper.has('li.favorites').should.be.false

    // add to playlists
    wrapper = initComponent({ playlists: false })
    wrapper.has('li.playlist').should.be.false

    // add to a new playlist
    wrapper = initComponent({ newPlaylist: false })
    wrapper.has('form.form-new-playlist').should.be.false
  })

  it('queue songs after current', () => {
    const wrapper = initComponent()
    const queueStub = sinon.stub(queueStore, 'queueAfterCurrent')
    const closeStub = sinon.stub(wrapper.vm, 'close')
    wrapper.click('li.after-current')
    queueStub.calledWith(songs).should.be.true
    closeStub.called.should.be.true
    queueStub.restore()
    closeStub.restore()
  })

  it('queue songs to bottom', () => {
    const wrapper = initComponent()
    const queueStub = sinon.stub(queueStore, 'queue')
    const closeStub = sinon.stub(wrapper.vm, 'close')
    wrapper.click('li.bottom-queue')
    queueStub.calledWith(songs).should.be.true
    closeStub.called.should.be.true
    queueStub.restore()
    closeStub.restore()
  })

  it('queue songs to top', () => {
    const wrapper = initComponent()
    const queueStub = sinon.stub(queueStore, 'queue')
    const closeStub = sinon.stub(wrapper.vm, 'close')
    wrapper.click('li.top-queue')
    queueStub.calledWith(songs, false, true).should.be.true
    closeStub.called.should.be.true
    queueStub.restore()
    closeStub.restore()
  })

  it('add songs to favorite', () => {
    const wrapper = initComponent()
    const likeStub = sinon.stub(favoriteStore, 'like')
    const closeStub = sinon.stub(wrapper.vm, 'close')
    wrapper.click('li.favorites')
    likeStub.calledWith(songs).should.be.true
    closeStub.called.should.be.true
    likeStub.restore()
    closeStub.restore()
  })

  it('add songs to existing playlist', () => {
    const playlists = factory('playlist', 3)
    playlistStore.all = playlists
    const wrapper = initComponent()
    const addSongsStub = sinon.stub(playlistStore, 'addSongs')
    const closeStub = sinon.stub(wrapper.vm, 'close')
    wrapper.findAll('li.playlist').at(1).click()
    addSongsStub.calledWith(playlists[1], songs).should.be.true
    closeStub.called.should.be.true
    addSongsStub.restore()
    closeStub.restore()
  })

  it('creates new playlist from songs', async done => {
    const storeStub = sinon.stub(playlistStore, 'store').callsFake(() => {
      return new Promise((resolve, reject) => {
        resolve(factory('playlist'))
      })
    })
    const wrapper = initComponent()
    const closeStub = sinon.stub(wrapper.vm, 'close')
    wrapper.setData({ newPlaylistName: 'Foo' })
    await wrapper.submit('form.form-new-playlist')
    storeStub.calledWith('Foo', songs).should.be.true
    closeStub.restore()
    done()
  })
})
