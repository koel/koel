import _ from 'lodash'
import Component from '@/components/song/add-to-menu.vue'
import factory from '@/__tests__/factory'
import { playlistStore, queueStore, favoriteStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'
import FunctionPropertyNames = jest.FunctionPropertyNames

describe('components/song/add-to-menu', () => {
  const config = {
    queue: true,
    favorites: true,
    playlists: true,
    newPlaylist: true
  }

  let songs: Song[]

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  const initComponent = (customConfig = {}, func = shallow) => {
    songs = factory<Song>('song', 5)
    return func(Component, {
      propsData: {
        songs,
        config: _.assign(_.clone(config), customConfig),
        showing: true
      }
    })
  }

  it('renders', () => {
    playlistStore.all = factory<Playlist>('playlist', 10)
    const wrapper = initComponent()
    expect(wrapper.html()).toMatch('Add 5 songs to')
    expect(wrapper.hasAll(
      'li.after-current',
      'li.bottom-queue',
      'li.top-queue',
      'li.favorites',
      'form.form-new-playlist'
    )).toBe(true)
    expect(wrapper.findAll('li.playlist')).toHaveLength(10)
  })

  it('supports different configurations', () => {
    // add to queue
    let wrapper = initComponent({ queue: false })
    expect(wrapper.hasNone('li.after-current', 'li.bottom-queue', 'li.top-queue')).toBe(true)

    // add to favorites
    wrapper = initComponent({ favorites: false })
    expect(wrapper.has('li.favorites')).toBe(false)

    // add to playlists
    wrapper = initComponent({ playlists: false })
    expect(wrapper.has('li.playlist')).toBe(false)

    // add to a new playlist
    wrapper = initComponent({ newPlaylist: false })
    expect(wrapper.has('form.form-new-playlist')).toBe(false)
  })

  it.each<[string, string, FunctionPropertyNames<typeof queueStore>]>([
    ['after current', '.after-current', 'queueAfterCurrent'],
    ['to bottom', '.bottom-queue', 'queue'],
    ['to top', '.top-queue', 'queueToTop']
  ])('queues songs %s when "%s" is clicked', (to, selector, queueFunc) => {
    const wrapper = initComponent()
    const queueMock = mock(queueStore, queueFunc)
    // @ts-ignore
    const closeMock = mock(wrapper.vm, 'close')
    wrapper.click(`li${selector}`)
    expect(queueMock).toHaveBeenCalledWith(songs)
    expect(closeMock).toHaveBeenCalled()
  })

  it('add songs to favorite', () => {
    const wrapper = initComponent()
    const likeStub = mock(favoriteStore, 'like')
    // @ts-ignore
    const closeStub = mock(wrapper.vm, 'close')
    wrapper.click('li.favorites')
    expect(likeStub).toHaveBeenCalledWith(songs)
    expect(closeStub).toHaveBeenCalled()
  })

  it('add songs to existing playlist', () => {
    const playlists = factory<Playlist>('playlist', 3)
    playlistStore.all = playlists
    const wrapper = initComponent()
    const addSongsStub = mock(playlistStore, 'addSongs')
    // @ts-ignore
    const closeStub = mock(wrapper.vm, 'close')
    wrapper.findAll('li.playlist').at(1).click()
    expect(addSongsStub).toHaveBeenCalledWith(playlists[1], songs)
    expect(closeStub).toHaveBeenCalled()
  })

  it('creates new playlist from songs', async () => {
    const storeStub = mock(playlistStore, 'store', new Promise(resolve => resolve(factory('playlist'))))
    const wrapper = initComponent()
    // @ts-ignore
    const closeStub = mock(wrapper.vm, 'close')
    wrapper.setData({ newPlaylistName: 'Foo' })
    wrapper.submit('form.form-new-playlist')
    await wrapper.vm.$nextTick()
    expect(storeStub).toHaveBeenCalledWith('Foo', songs)
    expect(closeStub).toHaveBeenCalled()
  })
})
