import Component from '@/components/song/SongContextMenu.vue'
import { downloadService } from '@/services'
import { songStore, playlistStore, queueStore, favoriteStore, commonStore, userStore } from '@/stores'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'
import { mount, Wrapper } from '@/__tests__/adapter'
import FunctionPropertyNames = jest.FunctionPropertyNames

describe('components/song/ContextMenuBase', () => {
  let songs: Song[], wrapper: Wrapper

  beforeEach(() => {
    userStore.current.is_admin = true
    commonStore.state.allowDownload = true
    songs = factory<Song>('song', 2)

    wrapper = mount(Component, {
      propsData: { songs },
      data: () => ({ copyable: true })
    })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', async () => {
    const selectors = [
      '.playback',
      '.go-to-album',
      '.go-to-artist',
      '.after-current',
      '.bottom-queue',
      '.top-queue',
      '.favorite'
    ]
    await (wrapper.vm as any).open(0, 0)
    expect(wrapper.hasAll(...selectors)).toBe(true)
  })

  it.each<[string, string, FunctionPropertyNames<typeof queueStore>]>([
    ['after current', '.after-current', 'queueAfterCurrent'],
    ['to bottom', '.bottom-queue', 'queue'],
    ['to top', '.top-queue', 'queueToTop']
  ])('queues songs %s when "%s" is clicked', async (to, selector, queueFunc) => {
    const m = mock(queueStore, queueFunc)
    await (wrapper.vm as any).open(0, 0)
    wrapper.click(selector)
    expect(m).toHaveBeenCalledWith(songs)
  })

  it('adds songs to favorite', async () => {
    const m = mock(favoriteStore, 'like')
    await (wrapper.vm as any).open(0, 0)
    wrapper.click('.favorite')
    expect(m).toHaveBeenCalledWith(songs)
  })

  it('adds songs to existing playlist', async () => {
    playlistStore.all = factory<Playlist>('playlist', 5)
    const m = mock(playlistStore, 'addSongs')
    await (wrapper.vm as any).open(0, 0)
    const html = wrapper.html()
    playlistStore.all.forEach(playlist => expect(html).toMatch(playlist.name))
    wrapper.click('.playlist')
    expect(m).toHaveBeenCalledWith(playlistStore.all[0], songs)
  })

  it('opens the edit form', async () => {
    const m = mock(eventBus, 'emit')
    userStore.current.is_admin = true
    await (wrapper.vm as any).open(0, 0)
    wrapper.click('.open-edit-form')
    expect(m).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', songs)
  })

  it('downloads', async () => {
    const m = mock(downloadService, 'fromSongs')
    await (wrapper.vm as any).open(0, 0)
    wrapper.click('.download')
    expect(m).toHaveBeenCalledWith(songs)
  })

  it('copies URL', async () => {
    const getShareableUrlMock = mock(songStore, 'getShareableUrl')
    const execCommandMock = mock(document, 'execCommand')

    const song = factory('song')
    await (wrapper.vm as any).open(0, 0)
    wrapper.setProps({ songs: [song] })
    wrapper.click('.copy-url')
    expect(getShareableUrlMock).toHaveBeenCalledWith(song)
    expect(execCommandMock).toHaveBeenCalledWith('copy')
  })
})
