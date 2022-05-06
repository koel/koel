import Component from '@/components/song/SongEditForm.vue'
import Typeahead from '@/components/ui/typeahead.vue'
import factory from '@/__tests__/factory'
import { songStore } from '@/stores'
import { songInfoService } from '@/services/info'
import { mock } from '@/__tests__/__helpers__'
import { mount } from '@/__tests__/adapter'

describe('components/song/edit-form', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('supports editing a single song', async () => {
    const song = factory<Song>('song', { infoRetrieved: true })
    const wrapper = mount(Component, {
      propsData: { songs: song }
    })

    await wrapper.vm.$nextTick()
    const metaHtml = wrapper.find('.meta').html()
    expect(metaHtml).toMatch(song.title)
    expect(metaHtml).toMatch(song.album.name)
    expect(metaHtml).toMatch(song.artist.name)

    await (wrapper.vm as any).open()
    expect(wrapper.has(Typeahead)).toBe(true)
    expect(wrapper.find('input[name=title]').value).toBe(song.title)
    expect(wrapper.find('input[name=album]').value).toBe(song.album.name)
    expect(wrapper.find('input[name=artist]').value).toBe(song.artist.name)
    expect(wrapper.find('input[name=track]').value).toBe(song.track.toString())

    wrapper.click('#editSongTabLyrics')
    expect(wrapper.find('textarea[name=lyrics]').value).toBe(song.lyrics)
  })

  it('fetches song information on demand', () => {
    const song = factory('song', { infoRetrieved: false })
    const fetchMock = mock(songInfoService, 'fetch')
    mount(Component, {
      propsData: { songs: song }
    })
    expect(fetchMock).toHaveBeenCalledWith(song)
  })

  it('supports editing multiple songs of multiple artists', () => {
    const wrapper = mount(Component, {
      propsData: {
        songs: factory('song', 3)
      }
    })

    const metaHtml = wrapper.find('.meta').html()
    expect(metaHtml).toMatch('3 songs selected')
    expect(metaHtml).toMatch('Mixed Artists')
    expect(metaHtml).toMatch('Mixed Albums')

    expect(wrapper.find('input[name=artist]').value).toBe('')
    expect(wrapper.find('input[name=album]').value).toBe('')
    expect(wrapper.has('.tabs .tab-lyrics')).toBe(false)
  })

  it('supports editing multiple songs of same artist and different albums', () => {
    const artist = factory<Artist>('artist')
    const wrapper = mount(Component, {
      propsData: {
        songs: factory('song', 5, {
          artist,
          artist_id: artist.id
        })
      }
    })

    const metaHtml = wrapper.find('.meta').html()
    expect(metaHtml).toMatch('5 songs selected')
    expect(metaHtml).toMatch(artist.name)
    expect(metaHtml).toMatch('Mixed Albums')

    expect(wrapper.find('input[name=artist]').value).toBe(artist.name)
    expect(wrapper.find('input[name=album]').value).toBe('')
    expect(wrapper.has('.tabs .tab-lyrics')).toBe(false)
  })

  it('supports editing multiple songs in same album', () => {
    const album = factory<Album>('album')
    const wrapper = mount(Component, {
      propsData: {
        songs: factory('song', 4, {
          album,
          album_id: album.id,
          artist: album.artist,
          artist_id: album.artist.id
        })
      }
    })

    const metaHtml = wrapper.find('.meta').html()
    expect(metaHtml).toMatch('4 songs selected')
    expect(metaHtml).toMatch(album.name)
    expect(metaHtml).toMatch(album.artist.name)

    expect(wrapper.find('input[name=artist]').value).toBe(album.artist.name)
    expect(wrapper.find('input[name=album]').value).toBe(album.name)
    expect(wrapper.has('.tabs .tab-lyrics')).toBe(false)
  })

  it('saves', async () => {
    const updateStub = mock(songStore, 'update')
    const songs = factory('song', 3)
    const formData = { foo: 'bar' }
    const wrapper = mount(Component, {
      data: () => ({ formData }),
      propsData: {
        songs
      }
    })
    wrapper.submit('form')
    await wrapper.vm.$nextTick()
    expect(updateStub).toHaveBeenCalledWith(songs, formData)
  })

  it('closes', async () => {
    const wrapper = mount(Component, {
      propsData: {
        songs: factory('song', 3)
      }
    })

    await wrapper.vm.$nextTick()
    wrapper.click('.btn-cancel')
    expect(wrapper.hasEmitted('close')).toBe(true)
  })
})
