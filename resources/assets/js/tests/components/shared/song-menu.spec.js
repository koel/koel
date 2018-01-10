import Component from '@/components/shared/song-menu.vue'
import { playback, download } from '@/services'
import { songStore, playlistStore, queueStore, favoriteStore, sharedStore, userStore } from '@/stores'
import { event } from '@/utils'
import factory from '@/tests/factory'

describe('components/shared/song-menu', () => {
  let songs

  beforeEach(() => {
    songs = factory('song', 10)
  })

  it('renders properly', () => {
    const wrapper = shallow(Component, { propsData: { songs }})      
    const selectors = [
      '.playback',
      '.go-to-album',
      '.go-to-artist',
      '.after-current',
      '.bottom-queue',
      '.top-queue',
      '.favorite'
    ]
    selectors.forEach(selector => {
      wrapper.contains(selector).should.be.true
    })
  })

  it('plays and pauses', () => {
    const wrapper = shallow(Component, { propsData: { songs }})      
  })

  it('queues songs after current', () => {
    const wrapper = shallow(Component, { propsData: { songs }})      
    const queueStub = sinon.stub(queueStore, 'queueAfterCurrent')
    wrapper.find('.after-current').trigger('click')
    queueStub.calledWith(songs).should.be.true
    queueStub.restore()
  })

  it('queues songs to bottom', () => {
    const wrapper = shallow(Component, { propsData: { songs }})      
    const queueStub = sinon.stub(queueStore, 'queue')
    wrapper.find('.bottom-queue').trigger('click')
    queueStub.calledWith(songs).should.be.true
    queueStub.restore()
  })

  it('queues songs to top', () => {
    const wrapper = shallow(Component, { propsData: { songs }})      
    const queueStub = sinon.stub(queueStore, 'queue')
    wrapper.find('.top-queue').trigger('click')
    queueStub.calledWith(songs, false, true).should.be.true
    queueStub.restore()
  })
  
  it('adds songs to favorite', () => {
    const wrapper = shallow(Component, { propsData: { songs }})      
    const likeStub = sinon.stub(favoriteStore, 'like')
    wrapper.find('.favorite').trigger('click')
    likeStub.calledWith(songs).should.be.true
    likeStub.restore()
  })

  it('adds songs to existing playlist', () => {
    playlistStore.all = factory('playlist', 5)
    const addStub = sinon.stub(playlistStore, 'addSongs')
    const wrapper = shallow(Component, { propsData: { songs }})      
    const html = wrapper.html()
    playlistStore.all.forEach(playlist => {
      html.should.contain(playlist.name) 
    })
    wrapper.find('.playlist').trigger('click')
    addStub.calledWith(playlistStore.all[0], songs).should.be.true
    addStub.restore()
  })

  it('opens the edit form', () => {
    const emitStub = sinon.stub(event, 'emit')
    userStore.current.is_admin = true
    const wrapper = shallow(Component, { propsData: { songs }})      
    wrapper.find('.open-edit-form').trigger('click')
    emitStub.calledWith('songs:edit', songs).should.be.true
    emitStub.restore()
  })

  it('downloads', () => {
    const downloadStub = sinon.stub(download, 'fromSongs')
    sharedStore.state.allowDownload = true
    const wrapper = shallow(Component, { propsData: { songs }})      
    wrapper.find('.download').trigger('click')
    downloadStub.calledWith(songs).should.be.true
    downloadStub.restore()
  })

  it('copies URL', () => {
    const getUrlStub = sinon.stub('getShareableUrl')
    const song = factory('song')
    const wrapper = shallow(Component, { 
      propsData: { songs: [song] },
      data: {
        copyable: true
      }
    })
    wrapper.find('.copy-url').trigger('click')
    getUrlStub.calledWith(song).should.be.true
  })
})
