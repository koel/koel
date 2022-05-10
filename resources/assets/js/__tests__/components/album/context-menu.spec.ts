import Component from '@/components/album/AlbumContextMenu.vue'
import factory from '@/__tests__/factory'
import { downloadService, playbackService } from '@/services'
import { commonStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/album/ContextMenuBase', () => {
  let album: Album

  beforeEach(() => {
    album = factory<Album>('album')
    // @ts-ignore
    commonStore.state = { allowDownload: true }
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('plays all', () => {
    const wrapper = shallow(Component, { propsData: { album } })
    const m = mock(playbackService, 'playAllInAlbum')

    wrapper.click('[data-testid=play]')
    expect(m).toHaveBeenCalledWith(album)
  })

  it('shuffles', () => {
    const wrapper = shallow(Component, { propsData: { album } })
    const m = mock(playbackService, 'playAllInAlbum')

    wrapper.click('[data-testid=shuffle]')
    expect(m).toHaveBeenCalledWith(album, true)
  })

  it('downloads', async () => {
    const wrapper = mount(Component, { propsData: { album } })
    await wrapper.vm.$nextTick()
    await (wrapper.vm as any).open(0, 0)
    const m = mock(downloadService, 'fromAlbum')

    wrapper.click('[data-testid=download]')
    expect(m).toHaveBeenCalledWith(album)
  })

  it('does not have a download item if not downloadable', () => {
    // @ts-ignore
    commonStore.state = { allowDownload: false }
    const wrapper = shallow(Component, { propsData: { album } })
    expect(wrapper.has('[data-testid=download]')).toBe(false)
  })
})
