import Component from '@/components/shared/song-list.vue'
import factory from '@/tests/factory'
import { event } from '@/utils'
import { songStore, queueStore } from '@/stores'
import { playback } from '@/services'
import router from '@/router'

describe('components/shared/song-list', () => {
  let songs

  beforeEach(() => {
    songs = factory('song', 20)
  })

  it('renders properly', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'allSongs'
    }})
  })

  it('informs parent to update meta data', () => {
    const emitStub = sinon.stub(event, 'emit')
    const getLengthStub = sinon.stub(songStore, 'getFormattedLength').callsFake(() => '12:34:56')
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'allSongs'
    }})
    
    getLengthStub.calledWith(songs).should.be.true
    emitStub.calledWith('updateMeta', {
      songCount: songs.length,
      totalLength: '12:34:56'
    }, undefined).should.be.true

    emitStub.restore()
    getLengthStub.restore()
  })

  it('triggers sort', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'allSongs'
    }})
    const sortStub = sinon.stub(wrapper.vm, 'sort') 
    const provider = {
      '.track-number': 'song.track',
      '.title': 'song.title',
      '.artist': ['song.album.artist.name', 'song.album.name', 'song.track'],
      '.album': ['song.album.name', 'song.track'],
      '.time': 'song.length'
    }
    for (let selector in provider) {
      wrapper.click(`.song-list-header ${selector}`)
      sortStub.calledWith(provider[selector]).should.be.true
    }
  })

  it('sorts', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'allSongs'
    }})
    
    // track number
    wrapper.click('.song-list-header .track-number')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.track >= wrapper.vm.songRows[i - 1].song.track).should.be.true
    }
    // second sort should be descending
    wrapper.click('.song-list-header .track-number')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.track <= wrapper.vm.songRows[i - 1].song.track).should.be.true
    }
    
    // title
    wrapper.click('.song-list-header .title')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.title >= wrapper.vm.songRows[i - 1].song.title).should.be.true
    }
    wrapper.click('.song-list-header .title')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.title <= wrapper.vm.songRows[i - 1].song.title).should.be.true
    }

    // artist
    wrapper.click('.song-list-header .artist')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.album.artist.name >= wrapper.vm.songRows[i - 1].song.album.artist.name).should.be.true
    }
    wrapper.click('.song-list-header .artist')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.album.artist.name <= wrapper.vm.songRows[i - 1].song.album.artist.name).should.be.true
    }
    
    // album
    wrapper.click('.song-list-header .album')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.album.name >= wrapper.vm.songRows[i - 1].song.album.name).should.be.true
    }
    wrapper.click('.song-list-header .album')
    for (let i = 1, j = wrapper.vm.songRows.length; i < j; ++i) {
      (wrapper.vm.songRows[i].song.album.name <= wrapper.vm.songRows[i - 1].song.album.name).should.be.true
    }
  })

  it('takes disc into account when sort an album song list', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'album'
    }})

    wrapper.vm.sort()
    wrapper.vm.sortKey.includes('song.disc').should.be.true
  })

  it('extracts search data from a search query', () => {
    const provider = {
      'foo': { keywords: 'foo', fields: ['song.title', 'song.album.name', 'song.artist.name'] },
      'foo in:title': { keywords: 'foo', fields: ['song.title'] },
      'in:album foo bar': { keywords: 'foo bar', fields: ['song.album.name'] },
      'foo bar in:artist': { keywords: 'foo bar', fields: ['song.artist.name'] },
      'foo in:album in:artist': { keywords: 'foo', fields: ['song.album.name', 'song.artist.name'] }
    }

    const wrapper = shallow(Component)
    for (let q in provider) {
      wrapper.vm.extractSearchData(q).should.eql(provider[q])
    }
  })

  it('plays when Enter is pressed with one selected song', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'allSongs'
    }})
    // select one row 
    wrapper.vm.filteredItems[0].selected = true

    const playStub = sinon.stub(playback, 'play')
    wrapper.find('.song-list-wrap').trigger('keydown.enter')
    playStub.calledWith(songs[0]).should.be.true
    playStub.restore()
  })

  it('plays when Enter is pressed in Queue screen', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'queue'
    }})

    const playStub = sinon.stub(playback, 'play')
    wrapper.vm.filteredItems[0].selected = true
    wrapper.vm.filteredItems[1].selected = true
    wrapper.find('.song-list-wrap').trigger('keydown.enter')
    playStub.calledWith(songs[0]).should.be.true
    playStub.restore()
  })

  it('queues when Enter is pressed in other screens', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'playlist'
    }})
    const queueStub = sinon.stub(queueStore, 'queue')
    const goStub = sinon.stub(router, 'go')  
    const playStub = sinon.stub(playback, 'play')

    // select 2 rows
    wrapper.vm.filteredItems[0].selected = true
    wrapper.vm.filteredItems[1].selected = true

    // simple Enter adds selected songs to bottom
    wrapper.find('.song-list-wrap').trigger('keydown.enter')
    queueStub.calledWith(wrapper.vm.selectedSongs, false, undefined).should.be.true
    // the current screen should be switched to "Queue"
    goStub.calledWith('queue').should.be.true

    // Shift+Enter queues to top
    wrapper.find('.song-list-wrap').trigger('keydown.enter', { shiftKey: true })
    queueStub.calledWith(wrapper.vm.selectedSongs, false, true).should.be.true
    goStub.calledWith('queue').should.be.true

    // Ctrl[+Shift]+Enter queues and plays the first song
    wrapper.find('.song-list-wrap').trigger('keydown.enter', { ctrlKey: true })
    playStub.calledWith(wrapper.vm.selectedSongs[0]).should.be.true
    playStub.called.should.be.true

    queueStub.restore()
    goStub.restore()
    playStub.restore()
  })

  it('selects all songs', () => {
    const wrapper = mount(Component, { propsData: {
      items: songs,
      type: 'playlist'
    }})
    wrapper.find('.song-list-wrap').trigger('keydown.a', { ctrlKey: true })
    wrapper.vm.filteredItems.forEach(item => item.selected.should.be.true)
  })
})
