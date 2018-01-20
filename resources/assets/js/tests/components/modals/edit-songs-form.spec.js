import Component from '@/components/modals/edit-songs-form.vue'
import factory from '@/tests/factory'
import { songInfo } from '@/services/info'
import { songStore } from '@/stores'

describe('components/modals/edit-songs-form', () => {
  // we stub songInfo.fetch() so that server calls aren't made
  let fetchInfoStub

  beforeEach(() => {
    fetchInfoStub = sinon.stub(songInfo, 'fetch')
  })

  afterEach(() => {
    fetchInfoStub.restore()
  })

  it('opens', async done => {
    const wrapper = shallow(Component)
    await wrapper.vm.open(factory('song', 3))
    wrapper.has('form').should.be.true
    done()
  })

  it('supports editing a single song', async done => {
    const song = factory('song')
    const wrapper = mount(Component)
    await wrapper.vm.open(song)

    const metaHtml = wrapper.find('.meta').html()
    metaHtml.should.contain(song.title)
    metaHtml.should.contain(song.album.name)
    metaHtml.should.contain(song.artist.name)

    wrapper.find('input[name=title]').value.should.equal(song.title)
    wrapper.find('input[name=album]').value.should.equal(song.album.name)
    wrapper.find('input[name=artist]').value.should.equal(song.artist.name)
    wrapper.find('input[name=track]').value.should.equal(song.track.toString())

    wrapper.click('.tabs .tab-lyrics')
    wrapper.find('textarea[name=lyrics]').value.should.equal(song.lyrics)

    done()
  })

  it('supports editing multiple songs of multiple artists', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.open(factory('song', 3))

    const metaHtml = wrapper.find('.meta').html()
    metaHtml.should.contain('3 songs selected')
    metaHtml.should.contain('Mixed Artists')
    metaHtml.should.contain('Mixed Albums')

    wrapper.find('input[name=artist]').value.should.be.empty
    wrapper.find('input[name=album]').value.should.be.empty
    wrapper.has('.tabs .tab-lyrics').should.be.false

    done()
  })

  it('supports editing multiple songs of same artist and different albums', async done => {
    const wrapper = mount(Component)
    const artist = factory('artist')
    const songs = factory('song', 5, {
      artist,
      artist_id: artist.id
    })
    await wrapper.vm.open(songs)

    const metaHtml = wrapper.find('.meta').html()
    metaHtml.should.contain('5 songs selected')
    metaHtml.should.contain(artist.name)
    metaHtml.should.contain('Mixed Albums')

    wrapper.find('input[name=artist]').value.should.equal(artist.name)
    wrapper.find('input[name=album]').value.should.be.empty
    wrapper.has('.tabs .tab-lyrics').should.be.false

    done()
  })

  it('supports editing multiple songs in same album', async done => {
    const wrapper = mount(Component)
    const album = factory('album')
    const songs = factory('song', 4, {
      album,
      album_id: album.id,
      artist: album.artist,
      artist_id: album.artist.id
    })
    await wrapper.vm.open(songs)

    const metaHtml = wrapper.find('.meta').html()
    metaHtml.should.contain('4 songs selected')
    metaHtml.should.contain(album.name)
    metaHtml.should.contain(album.artist.name)

    wrapper.find('input[name=artist]').value.should.equal(album.artist.name)
    wrapper.find('input[name=album]').value.should.equal(album.name)
    wrapper.has('.tabs .tab-lyrics').should.be.false

    done()
  })

  it('saves', async done => {
    const updateStub = sinon.stub(songStore, 'update')
    const wrapper = mount(Component)
    const songs = factory('song', 3)
    const formData = { foo: 'bar' }
    await wrapper.vm.open(songs)
    wrapper.setData({ formData })
    wrapper.submit('form')
    updateStub.calledWith(songs, formData).should.be.true

    done()
  })

  it('closes', async done => {
    const wrapper = shallow(Component)
    await wrapper.vm.open(factory('song', 3))
    await wrapper.vm.close()
    wrapper.has('form').should.be.false
    done()
  })
})
